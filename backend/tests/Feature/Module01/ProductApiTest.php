<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\StaffUsers;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

/**
 * Seed permissions and return an authenticated staff user with module permission.
 */
function authStaffForProducts(): StaffUsers
{
    $user = StaffUsers::factory()->create();
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    $user->givePermissionTo('access-module-01');
    Sanctum::actingAs($user, ['basic']);

    return $user;
}

/**
 * Issue a Sanctum token and return Authorization headers for API requests.
 */
function apiAuthHeaders(StaffUsers $user): array
{
    $token = $user->createToken('phpunit', ['basic'])->plainTextToken;

    return ['Authorization' => 'Bearer '.$token, 'User-Agent' => 'PHPUnit'];
}

it('lists products', function () {
    $user = authStaffForProducts();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeaders($user);

    Product::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/module-01/products', $headers);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            ['id', 'name', 'price', 'stock'],
        ],
    ]);
});

it('creates a product', function () {
    $user = authStaffForProducts();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeaders($user);

    $payload = [
        'sku' => 'SKU-'.uniqid(),
        'name' => 'Test Product',
        'price' => 19.99,
        'stock' => 10,
    ];

    $response = $this->postJson('/api/v1/module-01/products', $payload, $headers);

    $response->assertCreated();
    $response->assertJsonFragment([
        'name' => 'Test Product',
        'price' => '19.99',
        'stock' => 10,
    ]);
});

it('updates a product', function () {
    $user = authStaffForProducts();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeaders($user);

    $product = Product::factory()->create([
        'name' => 'Old Name',
        'price' => 29.99,
        'stock' => 5,
    ]);

    $payload = [
        'name' => 'New Name',
        'price' => 39.99,
        'stock' => 15,
    ];

    $response = $this->putJson('/api/v1/module-01/products/'.$product->id, $payload, $headers);

    $response->assertOk();
    $response->assertJsonFragment([
        'name' => 'New Name',
        'price' => '39.99',
        'stock' => 15,
    ]);
});

it('deletes a product', function () {
    $user = authStaffForProducts();
    $this->actingAs($user, 'staff');
    $headers = apiAuthHeaders($user);

    $product = Product::factory()->create();

    $response = $this->deleteJson('/api/v1/module-01/products/'.$product->id, [], $headers);

    $response->assertOk();
    $response->assertJson(['deleted' => true]);
});
