<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentLog;
use App\Models\StaffUsers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentLog>
 */
final class AgentLogFactory extends Factory
{
    /**
     * @var class-string<AgentLog>
     */
    protected $model = AgentLog::class;

    /**
     * Define el estado predeterminado del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_name' => fake()->randomElement([
                'AI-Assistant',
                'Scheduler',
                'Importer',
            ]),
            'user_id' => StaffUsers::factory(),
            'module' => fake()->randomElement([
                'Inventory',
                'Assistant',
            ]),
            'action' => fake()->randomElement([
                'list',
                'create',
                'update',
                'delete',
            ]),
            'status' => fake()->randomElement([
                'ok',
                'warn',
                'error',
            ]),
            'duration_ms' => fake()->numberBetween(1, 2000),
            'request_payload' => [
                'path' => '/api/test',
            ],
            'response_payload' => [
                'result' => 'success',
            ],
            'meta' => [
                'trace_id' => fake()->uuid(),
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Factory',
        ];
    }
}
