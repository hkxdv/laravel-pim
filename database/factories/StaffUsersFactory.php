<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StaffUsers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffUsers>
 */
final class StaffUsersFactory extends Factory
{
    /**
     * El nombre del modelo correspondiente de la factoría.
     *
     * @var class-string<StaffUsers>
     */
    protected $model = StaffUsers::class;

    /**
     * The current password being used by the factory.
     */
    private static string $password = '';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password !== ''
                ? self::$password
                : (self::$password = Hash::make('password')),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Inicializa atributos post-creación que no están en $fillable.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (StaffUsers $user): void {
            $user->forceFill([
                'password_changed_at' => now(),
                'last_activity' => now(),
            ])->save();
        });
    }
}
