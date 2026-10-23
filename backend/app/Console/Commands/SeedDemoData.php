<?php

namespace App\Console\Commands;

use App\DemoCatalog;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedDemoData extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:seed {--users=100000} {--products=1000} {--sales=200000} {--force}';

    protected $description = 'Genera usuarios, productos y compras ficticias por lotes sin duplicar la misma carga';

    public function handle(): int
    {
        $counts = [];
        foreach (['users' => 100000, 'products' => 10000, 'sales' => 500000] as $key => $max) {
            $value = filter_var($this->option($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => $max]]);
            if ($value === false) {
                $this->error("Cantidad inválida para {$key}: de 2 a {$max}.");

                return self::INVALID;
            }
            $counts[$key] = $value;
        }
        $adminPassword = env('DEMO_ADMIN_PASSWORD');
        if (! is_string($adminPassword) || strlen($adminPassword) < 12) {
            $this->error('Configura DEMO_ADMIN_PASSWORD con al menos 12 caracteres en backend/.env.');

            return self::FAILURE;
        }
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }
        $stamp = now()->toDateTimeString();
        DB::table('users')->insertOrIgnore(['name' => 'Administrador de práctica', 'email' => 'admin@prepracticas.test', 'password' => Hash::make($adminPassword), 'is_admin' => true, 'created_at' => $stamp, 'updated_at' => $stamp]);
        $hash = Hash::make(Str::random(64));
        for ($start = 1; $start < $counts['users']; $start += 500) {
            $rows = [];
            for ($i = $start; $i < min($start + 500, $counts['users']); $i++) {
                $rows[] = ['name' => DemoCatalog::personName(), 'email' => sprintf('demo-%06d@usuarios.example.invalid', $i), 'password' => $hash, 'is_admin' => false, 'created_at' => $stamp, 'updated_at' => $stamp];
            }
            DB::table('users')->insertOrIgnore($rows);
        }
        $this->info('Usuarios preparados.');
        for ($start = 1; $start <= $counts['products']; $start += 500) {
            $rows = [];
            for ($i = $start; $i <= min($start + 499, $counts['products']); $i++) {
                $rows[] = ['sku' => sprintf('DEMO-P-%06d', $i), ...DemoCatalog::product($i), 'price_cents' => 1000 + ($i * 137) % 100000, 'active' => true, 'created_at' => $stamp, 'updated_at' => $stamp];
            }
            DB::table('products')->insertOrIgnore($rows);
        }
        $this->info('Productos preparados.');
        $users = DB::table('users')->where('email', 'like', 'demo-%@usuarios.example.invalid')->orderBy('email')->limit($counts['users'] - 1)->pluck('id')->all();
        $products = DB::table('products')->where('sku', 'like', 'DEMO-P-%')->orderBy('sku')->limit($counts['products'])->get();
        for ($start = 1; $start <= $counts['sales']; $start += 500) {
            $end = min($start + 499, $counts['sales']);
            DB::transaction(function () use ($start, $end, $users, $products, $stamp): void {
                $references = array_map(fn (int $i): string => sprintf('DEMO-S-%07d', $i), range($start, $end));
                $existing = DB::table('sales')->whereIn('reference', $references)->pluck('id', 'reference');
                $sales = [];
                $details = [];
                for ($i = $start; $i <= $end; $i++) {
                    $reference = sprintf('DEMO-S-%07d', $i);
                    if ($existing->has($reference)) {
                        continue;
                    }
                    $items = [];
                    for ($j = 0; $j < min($i % 2 === 0 ? 3 : 1, $products->count()); $j++) {
                        $product = $products[($i * 7 + $j) % $products->count()];
                        $quantity = 1 + ($i + $j) % 3;
                        $items[] = ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price_cents' => $product->price_cents, 'line_total_cents' => $quantity * $product->price_cents, 'created_at' => $stamp, 'updated_at' => $stamp];
                    }
                    $sales[] = ['reference' => $reference, 'user_id' => $users[($i - 1) % count($users)], 'status' => 'paid', 'total_cents' => array_sum(array_column($items, 'line_total_cents')), 'created_at' => $stamp, 'updated_at' => $stamp];
                    $details[$reference] = $items;
                }
                if ($sales === []) {
                    return;
                }
                DB::table('sales')->insert($sales);
                $ids = DB::table('sales')->whereIn('reference', array_keys($details))->pluck('id', 'reference');
                $rows = [];
                foreach ($details as $reference => $items) {
                    foreach ($items as $item) {
                        $rows[] = ['sale_id' => $ids[$reference], ...$item];
                    }
                }
                DB::table('sale_items')->insert($rows);
            });
            if ($end % 10000 === 0 || $end === $counts['sales']) {
                $this->line("Ventas: {$end}/{$counts['sales']}");
            }
        }
        $this->info('Carga completada.');
        $this->table(['Tabla', 'Registros'], array_map(fn (string $table): array => [$table, DB::table($table)->count()], ['users', 'products', 'sales', 'sale_items']));

        return self::SUCCESS;
    }
}
