<?php

declare(strict_types=1);

namespace Modules\Sales\App\Providers;

use App\Interfaces\StatsServiceInterface;
use Illuminate\Support\ServiceProvider;
use Modules\Sales\App\Http\Controllers\SalesBaseController;
use Modules\Sales\App\Http\Controllers\SalesPanelController;
use Modules\Sales\App\Services\SalesStatsService;

final class SalesServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Sales';

    private string $moduleNameLower = 'sales';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Contextual binding para evitar colisiones globales del contrato StatsServiceInterface
        $this->app->when(SalesBaseController::class)
            ->needs(StatsServiceInterface::class)
            ->give(SalesStatsService::class);
        $this->app->when(SalesPanelController::class)
            ->needs(StatsServiceInterface::class)
            ->give(SalesStatsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerConfig();
    }

    /**
     * Register configs.
     */
    private function registerConfig(): void
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
