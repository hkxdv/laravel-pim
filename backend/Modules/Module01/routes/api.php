<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Module01\App\Http\Controllers\ProductController;
use Modules\Module01\App\Http\Controllers\ProductSpecDefinitionController;
use Modules\Module01\App\Http\Controllers\StockMovementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rutas permiten el consumo desde el frontend usando Axios y CSRF.
| El prefijo /api ya se aplica desde el RouteServiceProvider del módulo.
*/

Route::prefix('v1/module-01')
    ->middleware([
        'auth:sanctum',
        'abilities:basic',
        'permission:access-module-01,staff',
    ])
    ->group(function (): void {
        // Productos
        Route::get(
            '/products',
            [ProductController::class, 'index']
        );
        Route::get(
            '/products/search',
            [ProductController::class, 'search']
        );
        Route::get(
            '/products/{product}',
            [ProductController::class, 'show']
        );
        Route::post(
            '/products',
            [ProductController::class, 'store']
        );
        Route::put(
            '/products/{product}',
            [ProductController::class, 'update']
        );
        Route::delete(
            '/products/{product}',
            [ProductController::class, 'destroy']
        );

        // Definición de especificaciones de producto
        Route::get(
            '/spec-definition/{slug}',
            [ProductSpecDefinitionController::class, 'show']
        );

        // Movimientos de stock
        Route::get(
            '/stock-movements',
            [StockMovementController::class, 'index']
        );
        Route::post(
            '/stock-movements',
            [StockMovementController::class, 'store']
        );
    });
