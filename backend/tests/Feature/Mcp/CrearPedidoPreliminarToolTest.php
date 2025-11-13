<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\AgentOpsServer;
use App\Mcp\Tools\CrearPedidoPreliminarTool;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CrearPedidoPreliminarToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_borrador_de_pedido_con_multiplicador_e_ignora_inactivos(): void
    {
        // Arrange: productos
        Product::factory()->create([
            'name' => 'iPhone 14',
            'sku' => 'IP14',
            'price' => 999.00,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'iPhone 13',
            'sku' => 'IP13',
            'price' => 899.00,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'iPhone 12',
            'sku' => 'IP12',
            'price' => 599.00,
            'is_active' => false,
        ]);

        // Act
        $response = AgentOpsServer::tool(CrearPedidoPreliminarTool::class, [
            'items' => [
                'IP14' => 2,
                'IP13' => 1,
                'IP12' => 3,
            ],
            'multiplier' => '0.9',
            'redondear' => true,
            'ignorar_inactivos' => true,
        ]);

        // Assert
        $response->assertOk()
            ->assertSee('"status": "ok"')
            ->assertSee('"lines_count": 2')
            ->assertSee('"missing_count": 1')
            ->assertSee('"total": 2607.3')
            ->assertSee('"sku": "IP14"')
            ->assertSee('"line_total": 1798.2')
            ->assertSee('"sku": "IP13"')
            ->assertSee('"line_total": 809.1')
            ->assertSee('"missing_items"')
            ->assertSee('"IP12"');
    }
}
