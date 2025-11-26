<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Assistant\App\Http\Controllers\AssistantPanelController;

Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-assistant,staff',
])->name('internal.assistant.')->prefix('internal/assistant')->group(
    function (): void {
        Route::get(
            '/',
            [AssistantPanelController::class, 'showModulePanel']
        )->name('index');
    }
);
