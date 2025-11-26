<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\App\Models\Product;

final class TestProductsSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory()->count(25)->create();
    }
}
