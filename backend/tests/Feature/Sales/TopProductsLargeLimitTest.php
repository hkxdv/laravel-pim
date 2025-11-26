<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Models\StaffUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Inventory\App\Models\Product;
use Modules\Sales\App\Models\SalesItem;
use Modules\Sales\App\Models\SalesOrder;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class TopProductsLargeLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_products_limit_is_clamped(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);
        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        /** @var SalesOrder $order */
        $order = SalesOrder::query()->create([
            'client_id' => null,
            'user_id' => $user->id,
            'status' => 'requested',
            'total' => 0,
        ]);

        for ($i = 0; $i < 300; $i++) {
            /** @var Product $p */
            $p = Product::factory()->create([
                'sku' => sprintf('TP-%03d', $i),
                'price' => 10 + $i,
                'stock' => $i % 5,
            ]);

            SalesItem::query()->create([
                'sales_order_id' => $order->id,
                'product_id' => $p->id,
                'qty' => 1,
                'price' => $p->price,
            ]);
        }

        Sanctum::actingAs($user, ['basic'], 'staff');

        $resp = $this->getJson(
            '/api/v1/sales/orders/reports/top-products?limit=1000'
        )->assertOk()->json();

        $this->assertIsArray($resp);
        $this->assertLessThanOrEqual(200, count($resp));
        $this->assertGreaterThanOrEqual(50, count($resp));
    }

    public function test_top_products_combined_filters(): void
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
            'sku' => 'CF-AAA',
            'brand' => 'BrandCF',
            'model' => 'ModelCF',
            'price' => 10,
        ]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create([
            'sku' => 'CF-BBB',
            'brand' => 'BrandCF',
            'model' => 'Other',
            'price' => 20,
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
            'qty' => 3,
            'price' => 10,
        ]);
        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p2->id,
            'qty' => 1,
            'price' => 20,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $resp = $this->getJson(
            '/api/v1/sales/orders/reports/top-products?limit=10&sku=CF-&brand=BrandCF&model=ModelCF'
        )->assertOk()->json();

        $this->assertIsArray($resp);
        $this->assertCount(1, $resp);
    }
}
