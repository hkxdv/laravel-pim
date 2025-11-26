<?php

declare(strict_types=1);

use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;
use Modules\Assistant\App\Mcp\Tools\SearchProductTool;
use Modules\Inventory\App\Models\Product;

it(
    'search active products per term',
    function () {
        // Datos de prueba
        Product::query()->create([
            'sku' => 'IP14',
            'name' => 'iPhone 14',
            'brand' => 'Apple',
            'model' => 'A14',
            'barcode' => '1111111111111',
            'price' => '999.00',
            'stock' => 5,
            'is_active' => true,
            'metadata' => [],
        ]);

        Product::query()->create([
            'sku' => 'GS23',
            'name' => 'Galaxy S23',
            'brand' => 'Samsung',
            'model' => 'S23',
            'barcode' => '2222222222222',
            'price' => '799.00',
            'stock' => 10,
            'is_active' => true,
            'metadata' => [],
        ]);

        Product::query()->create([
            'sku' => 'NK3310',
            'name' => 'Nokia 3310',
            'brand' => 'Nokia',
            'model' => '3310',
            'barcode' => '3333333333333',
            'price' => '49.99',
            'stock' => 100,
            'is_active' => false,
            'metadata' => [],
        ]);

        $response = AgentOpsServer::tool(SearchProductTool::class, [
            'search' => 'iPhone',
            'is_active' => true,
            'per_page' => 5,
        ]);

        $response->assertOk();
        $response->assertSee('iPhone 14');
        $response->assertDontSee('Nokia 3310');
    }
);
