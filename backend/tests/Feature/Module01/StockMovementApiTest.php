<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\StaffUsers;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

/**
 * Helper to create an authenticated staff user with proper permission.
 */
function authStaffForMovements(): StaffUsers
{
    $user = StaffUsers::factory()->create();
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    $user->givePermissionTo('access-module-01');
    Sanctum::actingAs($user, ['basic']);

    return $user;
}

/**
 * Issue a Sanctum token and return Authorization headers.
 */
function apiAuthHeadersFor(StaffUsers $user): array
{
    $token = $user->createToken('phpunit', ['basic'])->plainTextToken;

    return ['Authorization' => 'Bearer '.$token, 'User-Agent' => 'PHPUnit'];
}

it('creates an IN stock movement and increments stock', function () {
    $user = authStaffForMovements();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeadersFor($user);

    $product = Product::factory()->create(['stock' => 5]);

    $payload = [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 3,
        'notes' => 'Initial stock intake',
        'performed_at' => now()->toISOString(),
    ];

    $res = $this->postJson('/api/v1/module-01/stock-movements', $payload, $headers);

    $res->assertCreated();
    $res->assertJsonStructure([
        'product' => ['id', 'stock'],
        'movement' => ['id', 'product_id', 'type', 'quantity', 'new_stock', 'notes'],
    ]);

    $product->refresh();
    expect($product->stock)->toBe(8);
});

it('prevents OUT movement when stock is insufficient', function () {
    $user = authStaffForMovements();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeadersFor($user);

    $product = Product::factory()->create(['stock' => 2]);

    $payload = [
        'product_id' => $product->id,
        'type' => 'out',
        'quantity' => 5,
        'notes' => 'Attempt to ship more than stock',
        'performed_at' => now()->toISOString(),
    ];

    try {
        $res = $this->postJson('/api/v1/module-01/stock-movements', $payload, $headers);
        $res->assertStatus(422);
        $res->assertJsonStructure(['message', 'errors']);
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
        expect($e->getMessage())->toBe('Stock insuficiente para realizar la salida.');
    }
});

it('creates an ADJUST movement that sets exact stock level', function () {
    $user = authStaffForMovements();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeadersFor($user);

    $product = Product::factory()->create(['stock' => 10]);

    $payload = [
        'product_id' => $product->id,
        'type' => 'adjust',
        'new_stock' => 4,
        'notes' => 'Inventory recount',
        'performed_at' => now()->toISOString(),
    ];

    $res = $this->postJson('/api/v1/module-01/stock-movements', $payload, $headers);

    $res->assertCreated();
    $res->assertJsonPath('movement.type', 'adjust');

    $product->refresh();
    expect($product->stock)->toBe(4);
});
