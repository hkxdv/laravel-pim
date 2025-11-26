<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Providers;

use App\Interfaces\StatsServiceInterface;
use App\Services\AdminStatsService;
use Illuminate\Support\ServiceProvider;

final class AssistantServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Assistant';

    private string $moduleNameLower = 'assistant';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        $this->app->bind(
            StatsServiceInterface::class,
            AdminStatsService::class
        );
    }

    public function boot(): void
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
