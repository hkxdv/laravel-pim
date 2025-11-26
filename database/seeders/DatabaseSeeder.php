<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed de la base de datos de la aplicación.
     */
    public function run(): void
    {
        // Ejecutar primero la siembra de roles y permisos, y luego los usuarios del sistema.
        $this->call([
            RolePermissionSeeder::class,
            SystemUsersSeeder::class,
            // TestProductsSeeder::class,
            // TestStockMovementsSeeder::class,
            RealProductsSeeder::class,

        ]);

        $this->command->info('Sincronizando permisos entre guards...');
        Artisan::call('permissions:sync-guards');
    }
}
