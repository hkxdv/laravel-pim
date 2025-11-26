<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;
use Modules\Assistant\App\Mcp\Tools\PriceForUserTool;
use Modules\Inventory\App\Models\Product;
use Tests\TestCase;

final class PriceForUserToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_price_for_user_with_multiplier(): void
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
        $response = AgentOpsServer::tool(PriceForUserTool::class, [
            'sku' => 'IP14',
            'quantity' => 2,
            'multiplier' => '0.8',
            'redondear' => true,
        ]);

        // Assert
        $response->assertOk()
            ->assertSee('"status": "ok"')
            ->assertSee('"sku": "IP14"')
            ->assertSee('"unit_base_price": 999')
            ->assertSee('"unit_final_price": 799.2')
            ->assertSee('"subtotal": 1598.4');
    }
}
