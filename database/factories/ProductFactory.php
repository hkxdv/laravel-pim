<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
final class ProductFactory extends Factory
{
    /**
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper('SKU-' . Str::random(10)),
            'name' => fake()->words(3, true),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('MOD-####')),
            'barcode' => fake()->ean13(),
            'price' => fake()->randomFloat(2, 1, 999.99),
            'stock' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'metadata' => [
                '_spec_slug' => 'screens',
                'screen_type' => 'lcd',
                'frame_included' => fake()->boolean(),
                'warranty_months' => fake()->numberBetween(0, 24),
                'glass_color' => fake()->randomElement(['black', 'white', 'gold', 'blue']),
            ],
        ];
    }
}