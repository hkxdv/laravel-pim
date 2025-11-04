<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StaffUsers;
use Illuminate\Database\Seeder;

final class GrantModule01PermissionToTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Asignando permiso access-module-01 a usuarios de prueba...');

        $emails = [
            'alice.fresh@domain.com',
            'bob.stale@domain.com',
            'charlie.missing@domain.com',
        ];

        foreach ($emails as $email) {
            /** @var StaffUsers|null $user */
            $user = StaffUsers::query()->where('email', $email)->first();
            if ($user) {
                $user->givePermissionTo('access-module-01');
                $this->command->info('Permiso asignado a: ' . $email);
            } else {
                $this->command->warn('Usuario no encontrado: ' . $email);
            }
        }

        $this->command->info('Asignación de permisos completada.');
    }
}
