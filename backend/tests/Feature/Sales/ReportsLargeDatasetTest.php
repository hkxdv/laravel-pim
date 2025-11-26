<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Models\StaffUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Models\StockMovement;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ReportsLargeDatasetTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_outs_returns_limited_results_on_large_dataset(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);

        Permission::query()->create([
            'name' => 'access-sales',
            'guard_name' => 'staff',
        ]);

        /** @var StaffUsers $user */
        $user = StaffUsers::factory()->create();
        $user->givePermissionTo('access-sales');

        // Crear un dataset grande
        $products = [];
        for ($i = 0; $i < 300; $i++) {
            $products[] = Product::factory()->create([
                'sku' => sprintf('DS-%03d', $i),
                'stock' => 0,
            ]);
        }

        foreach ($products as $p) {
            StockMovement::query()->create([
                'product_id' => $p->id,
                'user_id' => $user->id,
                'type' => 'out',
                'quantity' => 1,
                'new_stock' => 0,
                'notes' => 'Agotado',
                'performed_at' => now(),
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
        $this->assertLessThanOrEqual(200, count($resp));
        $this->assertGreaterThanOrEqual(100, count($resp));
    }
}
