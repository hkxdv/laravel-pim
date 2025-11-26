<?php

declare(strict_types=1);

use App\Models\StaffUsers;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Modules\Inventory\App\Models\Product;

function authStaffForProductsSearch(): StaffUsers
{
    $user = StaffUsers::factory()->create();
    Artisan::call('db:seed', [
        '--class' => RolePermissionSeeder::class,
    ]);
    $user->givePermissionTo('access-inventory');
    Sanctum::actingAs($user, ['basic']);

    return $user;
}

function apiAuthHeadersSearch(StaffUsers $user): array
{
    $token = $user->createToken('phpunit', ['basic'])->plainTextToken;

    return ['Authorization' => 'Bearer '.$token, 'User-Agent' => 'PHPUnit'];
}

it(
    'searches products via resolver in typesense mode with fallback',
    function () {
        putenv('SEARCH_MODE=typesense');
        $_ENV['SEARCH_MODE'] = 'typesense';
        $_SERVER['SEARCH_MODE'] = 'typesense';

        $user = authStaffForProductsSearch();
        $this->actingAs($user, 'staff');
        $headers = apiAuthHeadersSearch($user);

        Product::factory()->create([
            'name' => 'AA Battery',
            'brand' => 'Duracell',
            'model' => 'AA',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Laptop Pro',
            'brand' => 'Dell',
            'model' => 'XPS',
            'is_active' => true,
        ]);

        $response = $this->getJson(
            '/api/v1/inventory/products/search?q=Battery&per_page=10',
            $headers
        );

        $response->assertOk();
        $response->assertHeader('X-Search-Mode', 'typesense');
        $response->assertJsonStructure([
            'data' => [
                ['id', 'name', 'price', 'stock'],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
                'q',
                'mode',
            ],
        ]);
        $json = $response->json();
        expect($json['meta']['mode'])->toBe('typesense');

        $names = array_map(
            fn ($p) => $p['name'],
            $json['data']
        );
        expect($names)->toContain('AA Battery');
    }
);
