<?php

declare(strict_types=1);

namespace Database\Factories;

use Modules\Inventory\App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /**
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * Define el estado predeterminado del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => mb_strtoupper('SKU-' . Str::random(10)),
            'name' => fake()->words(3, true),
            'brand' => fake()->company(),
            'model' => mb_strtoupper(fake()->bothify('MOD-####')),
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

    /**
     * Estado: stock bajo (1-5).
     */
    public function lowStock(): static
    {
        return $this->state(fn(): array => [
            'stock' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Estado: stock cero.
     */
    public function zeroStock(): static
    {
        return $this->state(fn(): array => [
            'stock' => 0,
        ]);
    }

    /**
     * Estado: stock alto (50-200).
     */
    public function highStock(): static
    {
        return $this->state(fn(): array => [
            'stock' => fake()->numberBetween(50, 200),
        ]);
    }

    /**
     * Estado: producto inactivo.
     */
    public function inactive(): static
    {
        return $this->state(fn(): array => [
            'is_active' => false,
        ]);
    }
}
