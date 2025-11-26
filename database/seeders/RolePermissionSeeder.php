<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder para crear los roles, permisos y usuarios base del sistema.
 */
final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Roles y Permisos...');

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // CREAR PERMISOS - Solo los esenciales para acceso a módulos
        $permissions = [
            'access-admin',
            'access-inventory',
            'access-sales',
            'access-whatsapp',
            'access-assistant',

        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'staff',
            ]);
        }

        $this->command->info('Permisos creados.');

        // CREAR ROLES Y ASIGNAR PERMISOS
        // Rol: ADMIN (super-admin con todos los permisos)
        $roleAdmin = Role::query()->firstOrCreate([
            'name' => 'ADMIN',
            'guard_name' => 'staff',
        ]);
        $roleAdmin->givePermissionTo($permissions);

        // Rol: DEV (también es super-admin con todos los permisos)
        $roleDev = Role::query()->firstOrCreate([
            'name' => 'DEV',
            'guard_name' => 'staff',
        ]);
        $roleDev->givePermissionTo($permissions);

        // Roles de Módulos
        Role::query()->firstOrCreate([
            'name' => 'INVENTORY',
            'guard_name' => 'staff',
        ])->givePermissionTo('access-inventory');

        Role::query()->firstOrCreate([
            'name' => 'SALES',
            'guard_name' => 'staff',
        ])->givePermissionTo('access-sales');

        Role::query()->firstOrCreate([
            'name' => 'WHATSAPP',
            'guard_name' => 'staff',
        ])->givePermissionTo('access-whatsapp');

        Role::query()->firstOrCreate([
            'name' => 'ASSISTANT',
            'guard_name' => 'staff',
        ])->givePermissionTo('access-assistant');

        // NOTA: La creación de usuarios ahora es manejada por SystemUsersSeeder
        // para mejor separación de responsabilidades y flexibilidad.

        /* Log::info('Seeder de roles y permisos ejecutado:', [
            'roles_count' => Role::query()->count(),
            'permissions_count' => Permission::query()->count(),
            'roles' => Role::all(['id', 'name', 'guard_name'])->toArray(),
        ]); */

        $this->command->info('Seeder de roles y permisos completado exitosamente.');
    }
}
