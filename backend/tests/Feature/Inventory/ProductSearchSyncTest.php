<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Services\InventoryService;
use Tests\TestCase;

final class ProductSearchSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_results_after_create_with_fallback(): void
    {
        putenv('SEARCH_MODE=typesense');
        putenv('APP_RUNNING_IN_CONTAINER=true');
        putenv('SCOUT_DRIVER=null');

        /** @var Product $p */
        $p = Product::factory()->create([
            'sku' => 'SYNC-AAA',
            'name' => 'Producto Sync AAA',
            'brand' => 'SyncBrand',
            'model' => 'SyncModel',
            'price' => 9.99,
            'stock' => 3,
            'is_active' => true,
        ]);

        $svc = new InventoryService();
        $result = $svc->getAllProducts(
            ['search' => 'SYNC-AAA', 'per_page' => 10],
            10
        );
        $this->assertGreaterThanOrEqual(1, $result->total());
    }

    public function test_search_reflects_update_with_fallback(): void
    {
        putenv('SEARCH_MODE=typesense');
        putenv('APP_RUNNING_IN_CONTAINER=true');
        putenv('SCOUT_DRIVER=null');

        /** @var Product $p */
        $p = Product::factory()->create([
            'sku' => 'SYNC-BBB',
            'name' => 'Producto Sync BBB',
            'brand' => 'SyncBrand',
            'model' => 'SyncModel',
            'price' => 19.99,
            'stock' => 6,
            'is_active' => true,
        ]);

        $p->update(['name' => 'Producto Actualizado BBB']);

        $svc = new InventoryService();
        $result = $svc->getAllProducts(
            ['search' => 'Actualizado', 'per_page' => 10],
            10
        );
        $this->assertGreaterThanOrEqual(1, $result->total());
    }
}
