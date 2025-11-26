<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StaffUsers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Models\StockMovement;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    /**
     * @var class-string<StockMovement>
     */
    protected $model = StockMovement::class;

    /**
     * Define el estado predeterminado del modelo.
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
     * Movimiento de salida.
     */
    public function out(int $qty = 1): static
    {
        return $this->state(fn (): array => [
            'type' => 'out',
            'quantity' => $qty,
            'new_stock' => null,
        ]);
    }

    /**
     * Movimiento de ajuste.
     */
    public function adjust(int $toStock = 0): static
    {
        return $this->state(fn (): array => [
            'type' => 'adjust',
            'quantity' => null,
            'new_stock' => $toStock,
        ]);
    }

    /**
     * Movimiento de entrada.
     */
    public function in(int $qty = 1): static
    {
        return $this->state(fn (): array => [
            'type' => 'in',
            'quantity' => $qty,
            'new_stock' => null,
        ]);
    }

    /**
     * Vincula el movimiento al producto especificado.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (): array => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Vincula el movimiento al usuario especificado.
     */
    public function byUser(StaffUsers $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Notas personalizadas.
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn (): array => [
            'notes' => $notes,
        ]);
    }
}
