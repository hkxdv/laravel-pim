<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StaffUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Seeder para crear múltiples usuarios base del sistema (guard: staff)
 * a partir de variables definidas en un archivo .env adicional (.env.users).
 *
 * Convención de variables:
 *   USER_STAFF_{N}_NAME=Nombre Apellido
 *   USER_STAFF_{N}_EMAIL=correo@dominio.com
 *   USER_STAFF_{N}_PASSWORD=contraseña_segura
 *   USER_STAFF_{N}_ROLE=ROL_OPCIONAL (ej. ADMIN, DEV, MOD-01, ...)
 *
 * Donde N es un número entre 1 y 50 (por defecto iteramos 1..10, pero se puede ampliar).
 */
final class SystemUsersSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la base de datos con usuarios base.
     */
    public function run(): void
    {
        $this->command->info(
            'Iniciando seeder de Usuarios del Sistema (.env.users)...'
        );

        $created = 0;
        $updated = 0;
        $assignedRoles = 0;

        // Leer configuración centralizada para seeders con tipado seguro
        $maxConfig = config('seeders.users.staff.max');
        $max = is_int($maxConfig) ? $maxConfig : 10;
        $max = $max > 0 ? min($max, 50) : 10; // límite de seguridad a 50

        /** @var array<int, array{ email?: string, password?: string, name?: string, role?: string, force_password_update?: bool }> $staffList
         */
        $staffList = (array) (config('seeders.users.staff.list', []));

        foreach ($staffList as $index => $cfg) {
            /** @var array{ email?: string, password?: string, name?: string, role?: string, force_password_update?: bool} $cfg */
            $email = $cfg['email'] ?? null;
            $password = $cfg['password'] ?? null;
            $name = $cfg['name'] ?? ('Usuario '.($index + 1));
            $roleName = $cfg['role'] ?? null;

            if (! $email || ! $password) {
                continue; // Se salta si faltan datos esenciales
            }

            $user = StaffUsers::query()->where('email', $email)->first();
            if (! $user) {
                $user = StaffUsers::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);
                // Inicializar fecha de contraseña establecida
                $user->forceFill([
                    'password_changed_at' => now(),
                ])->save();
                $created++;
            } else {
                $needsUpdate = false;
                $updateData = [];

                if ($user->name !== $name) {
                    $updateData['name'] = $name;
                    $needsUpdate = true;
                }

                // Si se solicita actualizar la contraseña desde configuración
                $forcePassword = (bool) ($cfg['force_password_update']
                    ?? false
                );
                if ($forcePassword) {
                    $updateData['password'] = Hash::make($password);
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $user->update($updateData);
                    // Registrar cambio de contraseña en fecha actual si se forzó actualización
                    if ($forcePassword) {
                        $user->forceFill([
                            'password_changed_at' => now(),
                        ])->save();
                    }

                    $updated++;
                }
            }

            // Asignar rol si se especifica y existe
            if ($roleName !== null) {
                try {
                    $role = Role::findByName($roleName, 'staff');
                    if (! $user->hasRole($role)) {
                        $user->assignRole($role);
                        $assignedRoles++;
                    }
                } catch (Throwable $e) {
                    $this->command->warn(
                        sprintf("No se pudo asignar el rol '%s' al usuario %s: ", $roleName, $email).$e->getMessage()
                    );
                }
            }
        }

        Log::info(
            'SystemUsersSeeder ejecutado',
            [
                'created' => $created,
                'updated' => $updated,
                'assigned_roles' => $assignedRoles,
                'max_checked' => $max,
            ]
        );

        $this->command->info(
            sprintf('Usuarios creados: %d, actualizados: %d, roles asignados: %d.', $created, $updated, $assignedRoles)
        );
        $this->command->info('Seeder de usuarios del sistema completado.');
    }
}
