<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StockMovement;
use App\Models\Product;
use App\Models\StaffUsers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
final class StockMovementFactory extends Factory
{
    /**
     * @var class-string<StockMovement>
     */
    protected $model = StockMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => StaffUsers::factory(),
            'type' => 'in',
            'quantity' => 1,
            'new_stock' => null,
            'notes' => fake()->optional()->sentence(),
            'performed_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Factory',
        ];
    }

    /**
     * Outgoing movement.
     */
    public function out(): static
    {
        return $this->state(fn (): array => [
            'type' => 'out',
            'quantity' => 1,
            'new_stock' => null,
        ]);
    }

    /**
     * Adjustment movement.
     */
    public function adjust(int $toStock = 0): static
    {
        return $this->state(fn (): array => [
            'type' => 'adjust',
            'quantity' => null,
            'new_stock' => $toStock,
        ]);
    }
}