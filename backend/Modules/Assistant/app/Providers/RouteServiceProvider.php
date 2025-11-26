<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    private string $moduleNamespace = 'Modules\\Assistant\\App\\Http\\Controllers';

    public function boot(): void
    {
        $this->routes(function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(module_path('Assistant', 'routes/api.php'));

            Route::middleware('web')
                ->group(module_path('Assistant', 'routes/web.php'));
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
            ->group(module_path('Assistant', '/routes/web.php'));
    }

    private function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Assistant', '/routes/api.php'));
    }
}
