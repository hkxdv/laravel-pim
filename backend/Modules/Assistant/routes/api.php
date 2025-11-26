<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Assistant\App\Http\Controllers\AssistantChatController;

Route::prefix('v1/assistant')
    ->middleware([
        'throttle:60,1',
    ])
    ->withoutMiddleware([
        EnsureFrontendRequestsAreStateful::class,
        VerifyCsrfToken::class,
    ])->group(function (): void {
        Route::post('/chat', AssistantChatController::class);
    });
