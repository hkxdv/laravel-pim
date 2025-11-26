<?php

declare(strict_types=1);

use Modules\Assistant\App\Mcp\Prompts\DescribeProductPrompt;
use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;
use Modules\Inventory\App\Models\Product;

it(
    'generate a product summary including price',
    function () {
        $product = Product::query()->create([
            'sku' => 'IP14',
            'name' => 'iPhone 14',
            'brand' => 'Apple',
            'model' => 'A14',
            'barcode' => '1111111111111',
            'price' => '999.00',
            'stock' => 5,
            'is_active' => true,
            'metadata' => ['color' => 'negro'],
        ]);

        $response = AgentOpsServer::prompt(DescribeProductPrompt::class, [
            'sku' => $product->sku,
            'show_price' => true,
            'include_specifications' => false,
        ]);

        $response->assertOk();
        $response->assertSee('iPhone 14');
        $response->assertSee('$999.00');
    }
);
