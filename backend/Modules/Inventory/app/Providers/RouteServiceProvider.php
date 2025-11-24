<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Provider para el registro de rutas del módulo.
 * Define cómo se cargarán las rutas web y API.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * El namespace del controlador del módulo.
     */
    private string $moduleNamespace = 'Modules\\Inventory\\App\\Http\\Controllers';

    /**
     * Define las rutas del módulo.
     */
    public function boot(): void
    {
        $this->routes(function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(module_path('Inventory', 'routes/api.php'));

            Route::middleware('web')
                ->group(module_path('Inventory', 'routes/web.php'));
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
            ->group(module_path('Inventory', '/routes/web.php'));
    }

    /**
     * Define las rutas API para el módulo.
     */
    private function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Inventory', '/routes/api.php'));
    }
}
