<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Models\StaffUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Models\StockMovement;
use Modules\Sales\App\Models\SalesItem;
use Modules\Sales\App\Models\SalesOrder;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ReportsFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_products_filters_brand_model_and_limit(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);

        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        /** @var Product $p1 */
        $p1 = Product::factory()->create([
            'sku' => 'BR1-M1-AAA',
            'brand' => 'BrandOne',
            'model' => 'ModelOne',
            'price' => 10,
            'stock' => 5,
        ]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create([
            'sku' => 'BR1-M2-BBB',
            'brand' => 'BrandOne',
            'model' => 'ModelTwo',
            'price' => 15,
            'stock' => 3,
        ]);

        /** @var Product $p3 */
        $p3 = Product::factory()->create([
            'sku' => 'BR2-M3-CCC',
            'brand' => 'BrandTwo',
            'model' => 'ModelThree',
            'price' => 20,
            'stock' => 1,
        ]);

        /** @var SalesOrder $order */
        $order = SalesOrder::query()->create([
            'client_id' => null,
            'user_id' => $user->id,
            'status' => 'requested',
            'total' => 0,
        ]);
        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p1->id,
            'qty' => 2,
            'price' => 10,
        ]);
        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p2->id,
            'qty' => 1,
            'price' => 15,
        ]);
        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p3->id,
            'qty' => 4,
            'price' => 20,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $this->getJson(
            '/api/v1/sales/orders/reports/top-products?limit=2&brand=BrandOne'
        )->assertOk()->assertJson(fn ($json) => $json->has(2));

        $this->getJson(
            '/api/v1/sales/orders/reports/top-products?limit=1&model=ModelThree'
        )->assertOk()->assertJson(fn ($json) => $json->has(1));
    }

    public function test_stock_outs_filters_sku_brand_model_and_limit(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);

        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        /** @var Product $p1 */
        $p1 = Product::factory()->create([
            'sku' => 'S1-AAA',
            'brand' => 'BrandX',
            'model' => 'ModelX',
            'stock' => 0,
        ]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create([
            'sku' => 'S2-BBB',
            'brand' => 'BrandY',
            'model' => 'ModelY',
            'stock' => 0,
        ]);

        StockMovement::query()->create([
            'product_id' => $p1->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => 1,
            'new_stock' => 0,
            'notes' => 'Agotado',
            'performed_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
        StockMovement::query()->create([
            'product_id' => $p2->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => 1,
            'new_stock' => 0,
            'notes' => 'Agotado',
            'performed_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $start = now()->subMonth()->toDateString();
        $end = now()->addDay()->toDateString();

        $this->getJson(
            '/api/v1/sales/orders/reports/stock-outs?start_date='.$start.'&end_date='.$end.'&brand=BrandX&limit=10'
        )->assertOk()->assertJson(fn ($json) => $json->has(1));

        $this->getJson(
            '/api/v1/sales/orders/reports/stock-outs?start_date='.$start.'&end_date='.$end.'&sku=S2-BBB&limit=10'
        )->assertOk()->assertJson(fn ($json) => $json->has(1));
    }
}
