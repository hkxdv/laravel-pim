<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StaffUsers;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class TestStaffUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding usuarios de prueba para password_changed_at...');

        // Usuario FRESCO: password_changed_at ahora
        if (! StaffUsers::query()->where('email', 'alice.fresh@domain.com')->exists()) {
            $u1 = StaffUsers::factory()->create([
                'name' => 'Alice Fresh',
                'email' => 'alice.fresh@domain.com',
                'password' => Hash::make('Password123!'),
            ]);

            $u1->forceFill([
                'password_changed_at' => \Illuminate\Support\Facades\Date::now(),
                'last_activity' => \Illuminate\Support\Facades\Date::now()->subMinutes(2),
            ])->save();
            $this->command->info('Creado: Alice Fresh (password reciente)');
        } else {
            $this->command->info('Skip: Alice Fresh ya existe');
        }

        // Usuario VENCIDO: password_changed_at hace 120 días
        if (! StaffUsers::query()->where('email', 'bob.stale@domain.com')->exists()) {
            $u2 = StaffUsers::factory()->create([
                'name' => 'Bob Stale',
                'email' => 'bob.stale@domain.com',
                'password' => Hash::make('Password123!'),
            ]);

            $u2->forceFill([
                'password_changed_at' => \Illuminate\Support\Facades\Date::now()->subDays(120),
                'last_activity' => \Illuminate\Support\Facades\Date::now()->subDays(1),
            ])->save();
            $this->command->info('Creado: Bob Stale (password vencido)');
        } else {
            $this->command->info('Skip: Bob Stale ya existe');
        }

        // Usuario SIN MARCA: password_changed_at null
        if (! StaffUsers::query()->where('email', 'charlie.missing@domain.com')->exists()) {
            $u3 = StaffUsers::factory()->create([
                'name' => 'Charlie Missing',
                'email' => 'charlie.missing@domain.com',
                'password' => Hash::make('Password123!'),
            ]);

            $u3->forceFill([
                'password_changed_at' => null,
                'last_activity' => \Illuminate\Support\Facades\Date::now()->subMinutes(1),
            ])->save();
            $this->command->info('Creado: Charlie Missing (sin marca de cambio)');
        } else {
            $this->command->info('Skip: Charlie Missing ya existe');
        }

        $this->command->info('Seeder de pruebas completado.');
    }
}
