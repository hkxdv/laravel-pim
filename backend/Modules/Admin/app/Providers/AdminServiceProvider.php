<?php

declare(strict_types=1);

namespace Modules\Admin\App\Providers;

use App\Interfaces\StatsServiceInterface;
use App\Services\AdminStatsService;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Admin\App\Services\AdminStaffUserService;

/**
 * Provider principal del módulo Admin.
 * Registra y arranca todos los servicios específicos del módulo.
 */
final class AdminServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Admin';

    private string $moduleNameLower = 'admin';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        // Registrar la implementación de StaffUserManagerInterface
        $this->app->bind(
            StaffUserManagerInterface::class,
            AdminStaffUserService::class
        );

        // Registrar el servicio de estadísticas para el módulo Admin
        $this->app->bind(
            StatsServiceInterface::class,
            AdminStatsService::class
        );
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
            module_path(
                $this->moduleName,
                'config/config.php'
            ),
            $this->moduleNameLower
        );
    }
}
