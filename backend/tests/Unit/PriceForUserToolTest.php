<?php

declare(strict_types=1);

use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;
use Modules\Assistant\App\Mcp\Tools\PriceForUserTool;
use Modules\Inventory\App\Models\Product;

it(
    'calculates user price correctly',
    function () {
        $p = Product::factory()->create([
            'sku' => 'SKU-UNIT',
            'name' => 'Test',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = AgentOpsServer::tool(PriceForUserTool::class, [
            'sku' => $p->sku,
            'quantity' => 2,
            'multiplier' => '0.8',
            'round' => true,
        ]);

        $response->assertOk()
            ->assertSee('"status": "ok"')
            ->assertSee('"unit_base_price": 100')
            ->assertSee('"unit_final_price": 80')
            ->assertSee('"subtotal": 160');
    }
);
