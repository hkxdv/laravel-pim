<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\App\Http\Controllers\Orders\OrderController;

Route::prefix('v1/sales')
    ->middleware([
        'auth:sanctum',
        'abilities:basic',
        'permission:access-sales,staff',
    ])->group(
        function (): void {
            Route::get(
                '/orders',
                [OrderController::class, 'index']
            );
            Route::post(
                '/orders',
                [OrderController::class, 'store']
            );
            Route::post(
                '/orders/{order}/deliver',
                [OrderController::class, 'deliver']
            );
            Route::patch(
                '/orders/{order}',
                [OrderController::class, 'update']
            );
            Route::post(
                '/orders/{order}/cancel',
                [OrderController::class, 'cancel']
            );
            Route::get(
                '/orders/metrics',
                [OrderController::class, 'metrics']
            );
            Route::get(
                '/orders/reports/top-products',
                [OrderController::class, 'reportTopProducts']
            );
            Route::get(
                '/orders/reports/stock-outs',
                [OrderController::class, 'reportStockOuts']
            );
        }
    );
