<?php

declare(strict_types=1);

use App\Models\StaffUsers;
use Database\Factories\ProductFactory;
use Database\Factories\StaffUsersFactory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function authStaffForStockMovements(): StaffUsers
{
    $user = StaffUsersFactory::new()->create();
    Artisan::call('db:seed', [
        '--class' => RolePermissionSeeder::class,
    ]);
    $user->givePermissionTo('access-inventory');

    return $user;
}

it(
    'creates entry movement and increases stock',
    function () {
        $user = authStaffForStockMovements();
        $this->actingAs($user, 'staff');
        $this->withExceptionHandling();

        $product = ProductFactory::new()->create([
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 5,
                'notes' => 'Reposición',
            ]
        );

        $response->assertRedirect(
            route('internal.inventory.stock_movements.index')
        );

        $product->refresh();
        expect($product->stock)->toBe(15);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
        ]);
    }
);

it(
    'validates insufficient stock for output',
    function () {
        $user = authStaffForStockMovements();
        $this->actingAs($user, 'staff');
        $this->withExceptionHandling();

        $product = ProductFactory::new()->create([
            'stock' => 3,
            'is_active' => true,
        ]);

        $response = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => 5,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);

        $product->refresh();
        expect($product->stock)->toBe(3);
    }
);

it(
    'adjusts stock to an exact value',
    function () {
        $user = authStaffForStockMovements();
        $this->actingAs($user, 'staff');
        $this->withExceptionHandling();

        $product = ProductFactory::new()->create([
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'product_id' => $product->id,
                'type' => 'adjust',
                'new_stock' => 15,
                'notes' => 'Ajuste por inventario',
            ]
        );

        $response->assertRedirect(
            route('internal.inventory.stock_movements.index')
        );

        $product->refresh();
        expect($product->stock)->toBe(15);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjust',
            'new_stock' => 15,
        ]);
    }
);

it(
    'validates form rules',
    function () {
        $user = authStaffForStockMovements();
        $this->actingAs($user, 'staff');
        $this->withExceptionHandling();

        // Falta product_id
        $r1 = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'type' => 'in',
                'quantity' => 1,
            ]
        );
        $r1->assertStatus(422);
        $r1->assertJsonStructure(['message', 'errors']);

        // Tipo inválido
        $product = ProductFactory::new()->create();
        $r2 = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'product_id' => $product->id,
                'type' => 'foo',
                'quantity' => 1,
            ]
        );
        $r2->assertStatus(422);
        $r2->assertJsonStructure(['message', 'errors']);

        // Para entrada/salida, quantity es requerido
        $r3 = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'product_id' => $product->id,
                'type' => 'in',
            ]
        );
        $r3->assertStatus(422);
        $r3->assertJsonStructure(['message', 'errors']);

        // Para ajuste, new_stock es requerido
        $r4 = $this->postJson(
            route('internal.inventory.stock_movements.store'),
            [
                'product_id' => $product->id,
                'type' => 'adjust',
            ]
        );
        $r4->assertStatus(422);
        $r4->assertJsonStructure(['message', 'errors']);
    }
);
