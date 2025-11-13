<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\AgentOpsServer;
use App\Mcp\Tools\PrecioParaUsuarioTool;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PrecioParaUsuarioToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_precio_para_usuario_con_multiplicador(): void
    {
        // Arrange: producto base
        /** @var Product $product */
        $product = Product::factory()->create([
            'name' => 'iPhone 14',
            'sku' => 'IP14',
            'price' => 999.00,
            'is_active' => true,
        ]);

        // Act: invocamos la herramienta con multiplicador 0.8 y cantidad 2
        $response = AgentOpsServer::tool(PrecioParaUsuarioTool::class, [
            'sku' => 'IP14',
            'quantity' => 2,
            'multiplier' => '0.8',
            'redondear' => true,
        ]);

        // Debug temporal para inspeccionar el error
        // $response->dd();

        // Assert
        $response->assertOk()
            ->assertSee('"status": "ok"')
            ->assertSee('"sku": "IP14"')
            ->assertSee('"unit_base_price": 999')
            ->assertSee('"unit_final_price": 799.2')
            ->assertSee('"subtotal": 1598.4');
    }
}
