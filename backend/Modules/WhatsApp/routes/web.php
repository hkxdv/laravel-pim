<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\WhatsApp\App\Http\Controllers\Logs\ListController as LogsListController;
use Modules\WhatsApp\App\Http\Controllers\WhatsAppPanelController;

Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-whatsapp,staff',
])->prefix('internal/whatsapp')->name('internal.whatsapp.')
    ->group(
        function (): void {
            Route::get(
                '/',
                [WhatsAppPanelController::class, 'showModulePanel']
            )->name('index');

            Route::prefix('logs')->name('logs.')->group(
                function (): void {
                    Route::get('/', LogsListController::class)->name('index');
                }
            );
        }
    );
