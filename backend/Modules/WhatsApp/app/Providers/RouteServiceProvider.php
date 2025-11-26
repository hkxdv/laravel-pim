<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    private string $moduleNamespace = 'Modules\\WhatsApp\\App\\Http\\Controllers';

    public function boot(): void
    {
        $this->routes(function (): void {
            Route::middleware('web')
                ->group(module_path('WhatsApp', 'routes/web.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(module_path('WhatsApp', 'routes/api.php'));
        });
    }

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    private function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('WhatsApp', 'routes/web.php'));
    }

    private function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('WhatsApp', 'routes/api.php'));
    }
}
