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

final class OrderMetricsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_metrics_and_reports_basic(): void
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
            'sku' => 'SKU-AAA',
            'brand' => 'BrandX',
            'model' => 'ModelY',
            'price' => 50,
            'stock' => 100,
        ]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create([
            'sku' => 'SKU-BBB',
            'brand' => 'BrandX',
            'model' => 'ModelZ',
            'price' => 20,
            'stock' => 0,
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
            'price' => 50,
        ]);

        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p2->id,
            'qty' => 1,
            'price' => 20,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $this->getJson('/api/v1/sales/orders/metrics')->assertOk()->assertJsonStructure([
            'total_orders',
            'delivered_orders',
            'sum_totals',
        ]);

        $this->getJson(
            '/api/v1/sales/orders/reports/top-products?limit=5&sku=SKU-'
        )->assertOk()->assertJson(fn ($json) => $json->has(2));

        // Simular evento de agotamiento
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

        $start = now()->subMonth()->toDateString();
        $end = now()->addDay()->toDateString();

        $this->getJson(
            '/api/v1/sales/orders/reports/stock-outs?start_date='.$start.'&end_date='.$end.'&brand=BrandX'
        )->assertOk()->assertJson(fn ($json) => $json->has(1));
    }
}
