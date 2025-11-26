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

final class OrderDeliverMultipleItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deliver_order_with_multiple_items_and_locking(): void
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
            'price' => 10,
            'stock' => 5,
            'is_active' => true,
        ]);

        /** @var Product $p2 */
        $p2 = Product::factory()->create([
            'price' => 20,
            'stock' => 3,
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
            'product_id' => $p1->id,
            'qty' => 2,
            'price' => 10,
        ]);

        SalesItem::query()->create([
            'sales_order_id' => $order->id,
            'product_id' => $p2->id,
            'qty' => 1,
            'price' => 20,
        ]);

        Sanctum::actingAs($user, ['basic'], 'staff');

        $this->postJson(
            '/api/v1/sales/orders/'.$order->id.'/deliver',
            ['notes' => 'Entrega múltiple']
        )->assertOk()->assertJsonPath('status', 'delivered');

        $this->assertDatabaseHas('products', ['id' => $p1->id, 'stock' => 3]);
        $this->assertDatabaseHas('products', ['id' => $p2->id, 'stock' => 2]);
    }
}
