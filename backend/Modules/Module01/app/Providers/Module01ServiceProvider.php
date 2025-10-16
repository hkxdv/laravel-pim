<?php

declare(strict_types=1);

namespace Modules\Module01\App\Providers;

use App\Interfaces\StatsServiceInterface;
use Illuminate\Support\ServiceProvider;
use Modules\Module01\App\Http\Controllers\Module01BaseController;
use Modules\Module01\App\Http\Controllers\Module01PanelController;
use Modules\Module01\App\Services\Module01StatsService;

/**
 * Provider principal del módulo Module01.
 * Registra y arranca los servicios necesarios del módulo.
 */
final class Module01ServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Module01';

    /**
     * @var string
     */
    protected $moduleNameLower = 'module01';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Contextual binding para evitar colisiones globales del contrato StatsServiceInterface
        $this->app->when(Module01BaseController::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module01StatsService::class);
        $this->app->when(Module01PanelController::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module01StatsService::class);
    }

    public function boot(): void
    {
        $this->registerConfig();
    }

    /**
     * Registra la configuración del módulo.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
}
