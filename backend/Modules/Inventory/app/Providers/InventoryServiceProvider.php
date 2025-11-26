<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Providers;

use App\Interfaces\StatsServiceInterface;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\App\Http\Controllers\InventoryBaseController;
use Modules\Inventory\App\Http\Controllers\InventoryPanelController;
use Modules\Inventory\App\Interfaces\InventoryManagerInterface;
use Modules\Inventory\App\Services\InventoryService;
use Modules\Inventory\App\Services\InventoryStatsService;

/**
 * Provider principal del módulo.
 * Registra y arranca los servicios necesarios del módulo.
 */
final class InventoryServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Inventory';

    private string $moduleNameLower = 'inventory';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        // Binding del InventoryManagerInterface para operaciones de productos
        $this->app->bind(
            InventoryManagerInterface::class,
            InventoryService::class
        );

        // Contextual binding para evitar colisiones globales del contrato StatsServiceInterface
        $this->app->when(InventoryBaseController::class)
            ->needs(StatsServiceInterface::class)
            ->give(InventoryStatsService::class);
        $this->app->when(InventoryPanelController::class)
            ->needs(StatsServiceInterface::class)
            ->give(InventoryStatsService::class);
    }

    public function boot(): void
    {
        $this->registerConfig();
    }

    /**
     * Registra la configuración del módulo.
     */
    private function registerConfig(): void
    {
        $this->publishes([
            module_path(
                $this->moduleName,
                'config/config.php'
            ) => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
}
