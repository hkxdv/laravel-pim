<?php

declare(strict_types=1);

namespace Modules\Admin\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Provider para el registro de rutas del módulo Admin.
 * Define cómo se cargarán las rutas web y API del módulo.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * El namespace del controlador del módulo.
     */
    private string $moduleNamespace = 'Modules\\Admin\\App\\Http\\Controllers';

    /**
     * Registra los servicios del módulo.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Define las rutas del módulo.
     */
    public function boot(): void
    {
        $this->routes(function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(module_path('Admin', 'routes/api.php'));

            Route::middleware('web')
                ->group(module_path('Admin', 'routes/web.php'));
        });
    }

    /**
     * Define las rutas para el módulo.
     */
    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define las rutas web para el módulo.
     */
    private function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Admin', '/routes/web.php'));
    }

    /**
     * Define las rutas API para el módulo.
     */
    private function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Admin', '/routes/api.php'));
    }
}
