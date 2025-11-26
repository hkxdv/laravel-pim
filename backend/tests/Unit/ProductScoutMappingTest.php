<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\App\Models\Product;
use Tests\TestCase;

final class ProductScoutMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_contains_expected_fields(): void
    {
        /** @var Product $p */
        $p = Product::factory()->create([
            'sku' => 'SKU-XYZ',
            'name' => 'Producto XYZ',
            'brand' => 'MarcaX',
            'model' => 'ModeloY',
            'barcode' => '12345',
            'price' => 12.34,
            'stock' => 9,
            'is_active' => true,
            'metadata' => ['color' => 'rojo'],
        ]);

        $doc = $p->toSearchableArray();
        $this->assertIsArray($doc);
        $this->assertSame('SKU-XYZ', $doc['sku']);
        $this->assertSame('Producto XYZ', $doc['name']);
        $this->assertSame('MarcaX', $doc['brand']);
        $this->assertSame('ModeloY', $doc['model']);
        $this->assertSame(12.34, $doc['price']);
        $this->assertSame(9, $doc['stock']);
        $this->assertTrue($doc['is_active']);
        $this->assertIsArray($doc['metadata']);
        $this->assertArrayHasKey('created_at', $doc);
        $this->assertArrayHasKey('updated_at', $doc);
        $this->assertArrayHasKey('id', $doc);
    }
}
