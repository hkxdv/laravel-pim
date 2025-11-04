<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

final class TestProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Crear una cantidad razonable de productos de prueba
        Product::factory()->count(25)->create();
    }
}
