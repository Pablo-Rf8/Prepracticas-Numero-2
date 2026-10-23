<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin, 'sanctum');

        return $admin;
    }

    private function product(int $price = 1500): Product
    {
        return Product::factory()->create(['price_cents' => $price]);
    }

    private function saleData(User $user, Product $product): array
    {
        return ['user_id' => $user->id, 'status' => 'paid', 'items' => [['product_id' => $product->id, 'quantity' => 2]]];
    }

    public function test_public_users_are_paginated_and_never_expose_credentials(): void
    {
        $users = User::factory()->count(21)->create();
        $this->getJson('/api/public/users')->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 21)->assertJsonMissingPath('data.0.email')->assertJsonMissingPath('data.0.password');
        $this->getJson('/api/public/users?page=3')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/public/users/'.$users->first()->id)->assertOk()->assertJsonMissingPath('data.email');
        $this->getJson('/api/public/users?per_page=1000')->assertUnprocessable();
    }

    public function test_private_resources_require_authentication(): void
    {
        foreach (['users', 'products', 'sales'] as $resource) {
            $this->get('/api/private/'.$resource)->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
            $this->getJson('/api/private/'.$resource)->assertUnauthorized();
            $this->postJson('/api/private/'.$resource, [])->assertUnauthorized();
            $this->putJson('/api/private/'.$resource.'/1', [])->assertUnauthorized();
            $this->patchJson('/api/private/'.$resource.'/1', [])->assertUnauthorized();
            $this->deleteJson('/api/private/'.$resource.'/1')->assertUnauthorized();
        }
    }

    public function test_regular_users_cannot_manage_resources(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        foreach (['users', 'products', 'sales'] as $resource) {
            $this->getJson('/api/private/'.$resource)->assertForbidden();
            $this->postJson('/api/private/'.$resource, [])->assertForbidden();
        }
    }

    public function test_register_login_and_logout_use_real_revocable_tokens(): void
    {
        $data = ['name' => 'Ana', 'email' => 'ana@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'];
        $register = $this->postJson('/api/auth/register', $data)->assertCreated()->assertJsonMissingPath('data.password');
        $this->assertFalse(User::first()->is_admin);
        $this->assertTrue(Hash::check($data['password'], User::first()->password));
        $this->postJson('/api/auth/register', [...$data, 'email' => 'hacker@example.test', 'is_admin' => true])->assertUnprocessable();
        $this->postJson('/api/auth/login', ['email' => $data['email'], 'password' => 'wrong'])->assertUnprocessable();
        $login = $this->postJson('/api/auth/login', $data)->assertOk();
        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/auth/me')->assertOk();
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_admin_can_create_read_put_patch_and_delete_users(): void
    {
        $this->admin();
        $payload = ['name' => 'Nuevo', 'email' => 'nuevo@example.test', 'password' => 'Password123!'];
        $id = $this->postJson('/api/private/users', $payload)->assertCreated()->json('data.id');
        $this->getJson('/api/private/users')->assertOk()->assertJsonMissingPath('data.0.password');
        $this->getJson('/api/private/users/'.$id)->assertOk()->assertJsonPath('data.email', $payload['email']);
        $this->putJson('/api/private/users/'.$id, ['name' => 'Completo', 'email' => 'completo@example.test'])->assertOk();
        $this->putJson('/api/private/users/'.$id, ['name' => 'Incompleto'])->assertUnprocessable();
        $this->patchJson('/api/private/users/'.$id, ['name' => 'Parcial'])->assertOk()->assertJsonPath('data.name', 'Parcial');
        $this->deleteJson('/api/private/users/'.$id)->assertNoContent();
        $this->getJson('/api/private/users/'.$id)->assertNotFound();
    }

    public function test_admin_can_create_read_put_patch_and_delete_products(): void
    {
        $this->admin();
        $payload = ['sku' => 'TEST-1', 'name' => 'Mouse', 'price_cents' => 1000, 'active' => true];
        $id = $this->postJson('/api/private/products', $payload)->assertCreated()->json('data.id');
        $this->getJson('/api/public/products')->assertOk();
        $this->getJson('/api/public/products/'.$id)->assertOk();
        $this->getJson('/api/private/products/'.$id)->assertOk();
        $this->putJson('/api/private/products/'.$id, [...$payload, 'price_cents' => 2000])->assertOk()->assertJsonPath('data.price_cents', 2000);
        $this->putJson('/api/private/products/'.$id, ['name' => 'Incompleto'])->assertUnprocessable();
        $this->patchJson('/api/private/products/'.$id, ['name' => 'Mouse nuevo'])->assertOk();
        $this->deleteJson('/api/private/products/'.$id)->assertNoContent();
        $this->getJson('/api/public/products/'.$id)->assertNotFound();
    }

    public function test_sales_support_multiple_products_and_crud_with_server_totals(): void
    {
        $this->admin();
        $user = User::factory()->create();
        $p1 = $this->product(1500);
        $p2 = $this->product(2500);
        $data = $this->saleData($user, $p1);
        $data['items'][] = ['product_id' => $p2->id, 'quantity' => 3];
        $id = $this->postJson('/api/private/sales', $data)->assertCreated()->assertJsonPath('data.total_cents', 10500)->assertJsonCount(2, 'data.items')->json('data.id');
        $this->getJson('/api/private/sales')->assertOk();
        $this->getJson('/api/private/sales/'.$id)->assertOk()->assertJsonPath('data.user.id', $user->id);
        $p1->update(['price_cents' => 9999]);
        $this->patchJson('/api/private/sales/'.$id, ['status' => 'cancelled'])->assertOk()->assertJsonPath('data.total_cents', 10500)->assertJsonPath('data.items.0.unit_price_cents', 1500);
        $this->putJson('/api/private/sales/'.$id, $this->saleData($user, $p2))->assertOk()->assertJsonPath('data.total_cents', 5000)->assertJsonCount(1, 'data.items');
        $this->deleteJson('/api/private/sales/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $id]);
        $this->getJson('/api/private/sales/'.$id)->assertNotFound();
    }

    public function test_invalid_sales_do_not_partially_modify_existing_purchases(): void
    {
        $this->admin();
        $user = User::factory()->create();
        $p1 = $this->product();
        $p2 = $this->product();
        $sale = app(SaleService::class)->save($this->saleData($user, $p1));
        $p2->update(['active' => false]);
        $this->patchJson('/api/private/sales/'.$sale->id, ['items' => [['product_id' => $p2->id, 'quantity' => 1]]])->assertUnprocessable();
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total_cents' => 3000]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $p1->id]);
        $this->postJson('/api/private/sales', [...$this->saleData($user, $p1), 'total_cents' => 1])->assertUnprocessable();
        $this->postJson('/api/private/sales', [...$this->saleData($user, $p1), 'items' => [['product_id' => $p1->id, 'quantity' => 0]]])->assertUnprocessable();
        $this->postJson('/api/private/sales', [...$this->saleData($user, $p1), 'items' => [['product_id' => $p1->id, 'quantity' => 1], ['product_id' => $p1->id, 'quantity' => 2]]])->assertUnprocessable();
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_related_records_and_admin_accounts_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $product = $this->product();
        app(SaleService::class)->save($this->saleData($user, $product));
        $this->deleteJson('/api/private/users/'.$user->id)->assertConflict();
        $this->deleteJson('/api/private/users/'.$admin->id)->assertConflict();
        $this->deleteJson('/api/private/products/'.$product->id)->assertConflict();
    }

    public function test_customers_see_only_their_own_sales(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        app(SaleService::class)->save($this->saleData($user, $product));
        app(SaleService::class)->save($this->saleData($other, $product));
        $this->actingAs($user, 'sanctum')->getJson('/api/my/sales')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_id', $user->id);
    }

    public function test_small_demo_load_preserves_relations_totals_and_is_repeatable(): void
    {
        putenv('DEMO_ADMIN_PASSWORD=OnlyForTests123!');
        $_ENV['DEMO_ADMIN_PASSWORD'] = 'OnlyForTests123!';
        $this->artisan('demo:seed', ['--users' => 12, '--products' => 5, '--sales' => 20])->assertSuccessful();
        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseCount('products', 5);
        $this->assertDatabaseCount('sales', 20);
        $this->assertDatabaseCount('sale_items', 40);
        foreach (Sale::with('items')->get() as $sale) {
            $this->assertSame($sale->total_cents, $sale->items->sum('line_total_cents'));
        }
        $this->artisan('demo:seed', ['--users' => 12, '--products' => 5, '--sales' => 20])->assertSuccessful();
        $this->assertDatabaseCount('sales', 20);
        $this->assertDatabaseCount('sale_items', 40);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_refreshing_demo_names_preserves_accounts_and_purchase_amounts(): void
    {
        $customer = User::factory()->create(['name' => 'María López']);
        $demoUser = User::factory()->create(['email' => 'demo-000001@usuarios.example.invalid', 'name' => 'Old Example']);
        $product = Product::factory()->create(['sku' => 'DEMO-P-000001', 'name' => 'Lorem ipsum', 'price_cents' => 1500]);
        $ownProduct = Product::factory()->create(['name' => 'Producto propio']);
        $sale = app(SaleService::class)->save($this->saleData($demoUser, $product));

        $this->artisan('demo:refresh-labels')->assertSuccessful();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Arroz blanco 1 kg', 'price_cents' => 1500]);
        $this->assertDatabaseHas('products', ['id' => $ownProduct->id, 'name' => 'Producto propio']);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'name' => 'María López']);
        $this->assertNotSame('Old Example', $demoUser->fresh()->name);
        $this->assertSame($demoUser->password, $demoUser->fresh()->password);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total_cents' => 3000]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'unit_price_cents' => 1500, 'quantity' => 2]);
    }
}
