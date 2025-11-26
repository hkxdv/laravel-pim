<?php

declare(strict_types=1);

namespace Database\Seeders;

use Modules\Inventory\App\Models\Product;
use Illuminate\Database\Seeder;

final class TestProductsSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory()->count(25)->create();
    }
}
