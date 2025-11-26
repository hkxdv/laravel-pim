<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Providers;

use App\Interfaces\StatsServiceInterface;
use Illuminate\Support\ServiceProvider;
use Modules\WhatsApp\App\Http\Controllers\WhatsAppBaseController;
use Modules\WhatsApp\App\Http\Controllers\WhatsAppPanelController;
use Modules\WhatsApp\App\Services\WhatsAppStatsService;

final class WhatsAppServiceProvider extends ServiceProvider
{
    private string $moduleName = 'WhatsApp';

    private string $moduleNameLower = 'whatsapp';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->when(WhatsAppBaseController::class)
            ->needs(StatsServiceInterface::class)
            ->give(WhatsAppStatsService::class);
        $this->app->when(WhatsAppPanelController::class)
            ->needs(StatsServiceInterface::class)
            ->give(WhatsAppStatsService::class);
    }

    public function boot(): void
    {
        $this->registerConfig();
    }

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
