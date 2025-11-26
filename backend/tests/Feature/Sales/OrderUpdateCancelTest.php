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

final class OrderUpdateCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_status_to_prepared_and_cancel_order(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);

        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => 100,
            'stock' => 10,
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
            'product_id' => $product->id,
            'qty' => 2,
            'price' => 100,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $this->patchJson('/api/v1/sales/orders/'.$order->id, [
            'status' => 'prepared',
        ])->assertOk()->assertJsonPath('status', 'prepared');

        $this->postJson(
            '/api/v1/sales/orders/'.$order->id.'/cancel'
        )->assertOk()->assertJsonPath('status', 'draft');
    }
}
