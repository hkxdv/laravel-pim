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

final class OrderPreparedSkuErrorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepared_returns_errors_by_sku(): void
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
            'sku' => 'TEST-SKU',
            'price' => 100,
            'stock' => 0,
            'is_active' => true,
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
            'price' => 100,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $response = $this->patchJson(
            '/api/v1/sales/orders/'.$order->id,
            ['status' => 'prepared']
        );

        $response->assertStatus(422);
    }
}
