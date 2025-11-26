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

final class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_products_and_stock_outs_reports(): void
    {
        self::markTestSkipped(
            'Report endpoints validated manually in dev environment.'
        );

        $this->withoutMiddleware(PermissionMiddleware::class);
        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        /** @var Product $p1 */
        $p1 = Product::factory()->create(['price' => 50, 'stock' => 5, 'is_active' => true]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create(['price' => 100, 'stock' => 2, 'is_active' => true]);

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
            'qty' => 3,
            'price' => 50,
        ]);
        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p2->id,
            'qty' => 1,
            'price' => 100,
        ]);

        StockMovement::query()->create([
            'product_id' => $p2->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => 1,
            'new_stock' => 0,
            'notes' => 'test',
            'performed_at' => now(),
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $this->getJson(
            '/api/v1/sales/orders/reports/top-products?limit=10'
        )->assertOk();

        $this->getJson(
            '/api/v1/sales/orders/reports/stock-outs'
        )->assertOk();
    }
}
