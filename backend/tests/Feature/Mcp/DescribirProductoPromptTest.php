<?php

declare(strict_types=1);

use App\Mcp\Servers\AgentOpsServer;
use App\Models\Product;

it('genera un resumen del producto incluyendo precio', function () {
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

    $response = AgentOpsServer::prompt(App\Mcp\Prompts\DescribirProductoPrompt::class, [
        'sku' => $product->sku,
        'mostrar_precio' => true,
        'incluir_especificaciones' => false,
    ]);

    $response->assertOk();
    $response->assertSee('iPhone 14');
    $response->assertSee('$999.00');
});
