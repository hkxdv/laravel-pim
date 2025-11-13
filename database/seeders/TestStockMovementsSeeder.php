<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StaffUsers;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class TestStockMovementsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sembrando movimientos de stock de prueba...');

        // Crear/obtener usuario tester y asegurar permiso del módulo
        $tester = StaffUsers::query()->where('email', 'stock.tester@domain.com')->first();
        if (! $tester) {
            $tester = StaffUsers::factory()->create([
                'name' => 'Stock Tester',
                'email' => 'stock.tester@domain.com',
                'password' => Hash::make('Password123!'),
            ]);
        }

        if (! $tester->hasPermissionTo('access-module-01')) {
            $tester->givePermissionTo('access-module-01');
        }

        // Asegurar que haya productos suficientes
        if (Product::query()->count() < 10) {
            Product::factory()->count(10)->create();
        }

        $products = Product::query()->inRandomOrder()->limit(10)->get();

        foreach ($products as $product) {
            // Entrada (IN)
            $inQty = random_int(1, 5);
            $product->stock += $inQty;
            $product->save();

            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $tester->id,
                'type' => 'in',
                'quantity' => $inQty,
                'new_stock' => null,
                'notes' => 'Seeder: entrada inicial',
                'performed_at' => now()->subMinutes(random_int(5, 30)),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
            ]);

            // Salida (OUT), evitando stock negativo
            $outQty = min($product->stock, random_int(1, 3));
            $product->stock -= $outQty;
            $product->save();

            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $tester->id,
                'type' => 'out',
                'quantity' => $outQty,
                'new_stock' => null,
                'notes' => 'Seeder: salida controlada',
                'performed_at' => now()->subMinutes(random_int(1, 5)),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
            ]);

            // Ajuste (ADJUST)
            $adjustTo = random_int(0, 100);
            $product->stock = $adjustTo;
            $product->save();

            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $tester->id,
                'type' => 'adjust',
                'quantity' => 0,
                'new_stock' => $adjustTo,
                'notes' => 'Seeder: ajuste inventario',
                'performed_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
            ]);
        }

        $this->command->info('Movimientos de stock de prueba creados.');
    }
}
