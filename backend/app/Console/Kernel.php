<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Kernel de la consola de la aplicación.
 */
final class Kernel extends ConsoleKernel
{
    /**
     * Los comandos de Artisan proporcionados por la aplicación.
     *
     * Este array registra los comandos personalizados para que estén disponibles
     * a través de la interfaz de línea de comandos de Artisan.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        Commands\MakeProjectModuleCommand::class,
        Commands\SyncGuardPermissionsCommand::class,
    ];

    /**
     * Define la programación de comandos de la aplicación.
     */
    protected function schedule(Schedule $schedule): void {}

    /**
     * Registra los comandos para la aplicación.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
