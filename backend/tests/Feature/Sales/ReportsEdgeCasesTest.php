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

final class ReportsEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_products_no_data_when_filters_restrictive(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);
        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        /** @var Product $p */
        $p = Product::factory()->create([
            'sku' => 'AAA',
            'brand' => 'BrandA',
            'model' => 'ModelA',
            'price' => 10,
            'stock' => 5,
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
            'product_id' => $p->id,
            'qty' => 1,
            'price' => 10,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $this->getJson(
            '/api/v1/sales/orders/reports/top-products?brand=BrandX&model=ModelX&limit=10'
        )->assertOk()->assertExactJson([]);
    }

    public function test_stock_outs_large_limit(): void
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
        $p1 = Product::factory()->create(['sku' => 'LIM-1', 'stock' => 0]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create(['sku' => 'LIM-2', 'stock' => 0]);

        /** @var Product $p3 */
        $p3 = Product::factory()->create(['sku' => 'LIM-3', 'stock' => 0]);

        foreach ([$p1, $p2, $p3] as $idx => $prod) {
            StockMovement::query()->create([
                'product_id' => $prod->id,
                'user_id' => $user->id,
                'type' => 'out',
                'quantity' => 1,
                'new_stock' => 0,
                'notes' => 'Agotado',
                'performed_at' => now()->subDays(2 - $idx),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
            ]);
        }

        Sanctum::actingAs($user, ['basic'], 'staff');
        $start = now()->subMonth()->toDateString();
        $end = now()->addDay()->toDateString();

        $resp = $this->getJson(
            '/api/v1/sales/orders/reports/stock-outs?start_date='.$start.'&end_date='.$end.'&limit=200'
        )->assertOk()->json();

        $this->assertIsArray($resp);
        $this->assertGreaterThanOrEqual(3, count($resp));
    }
}
