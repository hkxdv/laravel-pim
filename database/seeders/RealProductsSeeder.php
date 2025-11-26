<?php

declare(strict_types=1);

namespace Database\Seeders;

use Modules\Inventory\App\Models\Product;
use Illuminate\Database\Seeder;

final class RealProductsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'sku' => 'A03S-CHARGE-PORT',
                'name' => 'Centro de Carga Samsung A03s',
                'brand' => 'Samsung',
                'model' => 'A03s',
                'barcode' => '8806098765432',
                'price' => 199.00,
                'stock' => 25,
                'is_active' => true,
                'metadata' => ['category' => 'Centro de Carga', 'image_url' => 'https://example.com/img/a03s-charge.jpg'],
            ],
            [
                'sku' => 'IP14-BATTERY',
                'name' => 'Batería iPhone 14',
                'brand' => 'Apple',
                'model' => 'iPhone 14',
                'barcode' => '190199876543',
                'price' => 799.20,
                'stock' => 12,
                'is_active' => true,
                'metadata' => ['category' => 'Batería', 'image_url' => 'https://example.com/img/iphone14-battery.jpg'],
            ],
            [
                'sku' => 'IP14-SCREEN',
                'name' => 'Pantalla iPhone 14',
                'brand' => 'Apple',
                'model' => 'iPhone 14',
                'barcode' => '190199123456',
                'price' => 1499.00,
                'stock' => 8,
                'is_active' => true,
                'metadata' => ['category' => 'Pantalla', 'image_url' => 'https://example.com/img/iphone14-screen.jpg'],
            ],
            [
                'sku' => 'GS23-USB-CABLE',
                'name' => 'Cable USB-C Samsung S23',
                'brand' => 'Samsung',
                'model' => 'S23',
                'barcode' => '8806091234567',
                'price' => 129.90,
                'stock' => 100,
                'is_active' => true,
                'metadata' => ['category' => 'Cable', 'image_url' => 'https://example.com/img/s23-usb-c.jpg'],
            ],
            [
                'sku' => 'IP14-CASE',
                'name' => 'Funda iPhone 14 Transparente',
                'brand' => 'Apple',
                'model' => 'iPhone 14',
                'barcode' => '190199765432',
                'price' => 249.50,
                'stock' => 60,
                'is_active' => true,
                'metadata' => ['category' => 'Accesorio', 'image_url' => 'https://example.com/img/iphone14-case.jpg'],
            ],
            [
                'sku' => 'A03S-SCREEN',
                'name' => 'Pantalla Samsung A03s',
                'brand' => 'Samsung',
                'model' => 'A03s',
                'barcode' => '8806090001112',
                'price' => 699.00,
                'stock' => 14,
                'is_active' => true,
                'metadata' => ['category' => 'Pantalla', 'image_url' => 'https://example.com/img/a03s-screen.jpg'],
            ],
            [
                'sku' => 'IP14-CHARGE-PORT',
                'name' => 'Centro de Carga iPhone 14',
                'brand' => 'Apple',
                'model' => 'iPhone 14',
                'barcode' => '1901990002223',
                'price' => 899.00,
                'stock' => 10,
                'is_active' => true,
                'metadata' => ['category' => 'Centro de Carga', 'image_url' => 'https://example.com/img/iphone14-charge.jpg'],
            ],
            [
                'sku' => 'S23-BATTERY',
                'name' => 'Batería Samsung S23',
                'brand' => 'Samsung',
                'model' => 'S23',
                'barcode' => '8806092222333',
                'price' => 649.90,
                'stock' => 25,
                'is_active' => true,
                'metadata' => ['category' => 'Batería', 'image_url' => 'https://example.com/img/s23-battery.jpg'],
            ],
            [
                'sku' => 'A03S-FLEX-CONNECTOR',
                'name' => 'Flex Connector Samsung A03s',
                'brand' => 'Samsung',
                'model' => 'A03s',
                'barcode' => '8806093333444',
                'price' => 179.00,
                'stock' => 40,
                'is_active' => true,
                'metadata' => ['category' => 'Flex', 'image_url' => 'https://example.com/img/a03s-flex.jpg'],
            ],
            [
                'sku' => 'IP14-BARCODE-READER',
                'name' => 'Lector Código de Barras iPhone 14 (Accesorio)',
                'brand' => 'Apple',
                'model' => 'iPhone 14',
                'barcode' => '1901994444555',
                'price' => 399.90,
                'stock' => 5,
                'is_active' => false,
                'metadata' => ['category' => 'Accesorio', 'image_url' => 'https://example.com/img/iphone14-barcode-reader.jpg'],
            ],
        ];

        foreach ($items as $it) {
            Product::query()->updateOrCreate(
                ['sku' => $it['sku']],
                $it
            );
        }
    }
}
