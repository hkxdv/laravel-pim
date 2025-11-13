<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Pruebas de creación/almacenamiento de movimientos de stock (Módulo 01).
 */
uses(RefreshDatabase::class);

it('crea movimiento de entrada y aumenta el stock', function () {
    $user = Database\Factories\StaffUsersFactory::new()->create();
    // Seed permisos y asignar permiso requerido para el módulo
    Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\RolePermissionSeeder::class]);
    $user->givePermissionTo('access-module-01');
    $this->actingAs($user, 'staff');

    $product = Database\Factories\ProductFactory::new()->create([
        'stock' => 10,
        'is_active' => true,
    ]);

    $response = $this->post(route('internal.module01.stock_movements.store'), [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 5,
        'notes' => 'Reposición',
    ]);

    $response->assertRedirect(route('internal.module01.stock_movements.index'));

    $product->refresh();
    expect($product->stock)->toBe(15);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 5,
    ]);
});

it('valida stock insuficiente para salida', function () {
    $user = Database\Factories\StaffUsersFactory::new()->create();
    Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\RolePermissionSeeder::class]);
    $user->givePermissionTo('access-module-01');
    $this->actingAs($user, 'staff');
    $this->withExceptionHandling();

    $product = Database\Factories\ProductFactory::new()->create([
        'stock' => 3,
        'is_active' => true,
    ]);

    $response = $this->postJson(route('internal.module01.stock_movements.store'), [
        'product_id' => $product->id,
        'type' => 'out',
        'quantity' => 5,
    ]);

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);

    $product->refresh();
    expect($product->stock)->toBe(3);
});

it('ajusta stock a un valor exacto', function () {
    $user = Database\Factories\StaffUsersFactory::new()->create();
    Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\RolePermissionSeeder::class]);
    $user->givePermissionTo('access-module-01');
    $this->actingAs($user, 'staff');
    $this->withExceptionHandling();

    $product = Database\Factories\ProductFactory::new()->create([
        'stock' => 5,
        'is_active' => true,
    ]);

    $response = $this->post(route('internal.module01.stock_movements.store'), [
        'product_id' => $product->id,
        'type' => 'adjust',
        'new_stock' => 15,
        'notes' => 'Ajuste por inventario',
    ]);

    $response->assertRedirect(route('internal.module01.stock_movements.index'));

    $product->refresh();
    expect($product->stock)->toBe(15);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjust',
        'new_stock' => 15,
    ]);
});

it('aplica reglas de validación del formulario', function () {
    $user = Database\Factories\StaffUsersFactory::new()->create();
    Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\RolePermissionSeeder::class]);
    $user->givePermissionTo('access-module-01');
    $this->actingAs($user, 'staff');
    $this->withExceptionHandling();

    // Falta product_id
    $r1 = $this->postJson(route('internal.module01.stock_movements.store'), [
        'type' => 'in',
        'quantity' => 1,
    ]);
    $r1->assertStatus(422);
    $r1->assertJsonStructure(['message', 'errors']);

    // Tipo inválido
    $product = Database\Factories\ProductFactory::new()->create();
    $r2 = $this->postJson(route('internal.module01.stock_movements.store'), [
        'product_id' => $product->id,
        'type' => 'foo',
        'quantity' => 1,
    ]);
    $r2->assertStatus(422);
    $r2->assertJsonStructure(['message', 'errors']);

    // Para entrada/salida, quantity es requerido
    $r3 = $this->postJson(route('internal.module01.stock_movements.store'), [
        'product_id' => $product->id,
        'type' => 'in',
    ]);
    $r3->assertStatus(422);
    $r3->assertJsonStructure(['message', 'errors']);

    // Para ajuste, new_stock es requerido
    $r4 = $this->postJson(route('internal.module01.stock_movements.store'), [
        'product_id' => $product->id,
        'type' => 'adjust',
    ]);
    $r4->assertStatus(422);
    $r4->assertJsonStructure(['message', 'errors']);
});
