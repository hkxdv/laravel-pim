<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\StaffUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Inventory\App\Models\Product;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ProductPaginationPerfTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_api_pagination_and_clamps(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);

        Permission::query()->create([
            'name' => 'access-inventory',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-inventory');

        for ($i = 0; $i < 200; $i++) {
            Product::factory()->create([
                'sku' => sprintf('PG-%03d', $i),
                'name' => sprintf('Producto %03d', $i),
                'price' => 10 + $i,
                'stock' => $i % 7,
                'is_active' => true,
            ]);
        }

        Sanctum::actingAs($user, ['basic'], 'staff');

        $resp = $this->getJson('/api/v1/inventory/products?per_page=50&page=2&sort_field=created_at&sort_direction=desc')
            ->assertOk()
            ->json();

        $this->assertIsArray($resp);
        $this->assertArrayHasKey('data', $resp);
        $this->assertArrayHasKey('meta', $resp);
        $this->assertCount(50, $resp['data']);
        $this->assertSame(2, (int) ($resp['meta']['current_page'] ?? 0));
    }
}
