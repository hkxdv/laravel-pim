<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\WhatsApp\App\Http\Controllers\TwilioWebhookController;

Route::prefix('v1/whatsapp')
    ->middleware([
        'throttle:60,1',
    ])
    ->withoutMiddleware([
        EnsureFrontendRequestsAreStateful::class,
        VerifyCsrfToken::class,
    ])
    ->group(
        function (): void {
            Route::post(
                '/webhooks/twilio',
                [TwilioWebhookController::class, 'handle']
            );
        }
    );
