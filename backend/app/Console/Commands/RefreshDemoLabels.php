<?php

namespace App\Console\Commands;

use App\DemoCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('demo:refresh-labels {--force}')]
#[Description('Actualiza nombres y descripciones de los datos de práctica sin cambiar sus relaciones ni importes')]
class RefreshDemoLabels extends Command
{
    use ConfirmableTrait;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }
        DB::table('products')->where('sku', 'like', 'DEMO-P-%')->orderBy('id')->chunkById(500, function (Collection $products): void {
            DB::transaction(function () use ($products): void {
                foreach ($products as $product) {
                    $number = substr($product->sku, strlen('DEMO-P-'));
                    if (ctype_digit($number) && (int) $number > 0) {
                        DB::table('products')->where('id', $product->id)->update(DemoCatalog::product((int) $number));
                    }
                }
            });
        });
        $this->info('Productos actualizados.');
        DB::table('users')->where('email', 'like', 'demo-%@usuarios.example.invalid')->where('is_admin', false)->orderBy('id')->chunkById(500, function (Collection $users): void {
            DB::transaction(function () use ($users): void {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update(['name' => DemoCatalog::personName()]);
                }
            });
        });
        $this->info('Usuarios actualizados. Se conservaron las cuentas propias, contraseñas, precios y ventas.');

        return self::SUCCESS;
    }
}
