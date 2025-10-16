<?php

declare(strict_types=1);

namespace Modules\Module01\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Provider para el registro de rutas del módulo Module01.
 * Define cómo se cargarán las rutas web y API del módulo.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * El namespace del controlador del módulo.
     */
    protected string $moduleNamespace = 'Modules\\Module01\\App\\Http\\Controllers';

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
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(module_path('Module01', 'routes/api.php'));

            Route::middleware('web')
                ->group(module_path('Module01', 'routes/web.php'));
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
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Module01', '/routes/web.php'));
    }

    /**
     * Define las rutas API para el módulo.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Module01', '/routes/api.php'));
    }
}
