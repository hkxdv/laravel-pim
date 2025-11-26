<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\App\Http\Controllers\Orders\OrdersPageController;
use Modules\Sales\App\Http\Controllers\Reports\ReportsPageController;
use Modules\Sales\App\Http\Controllers\SalesPanelController;

/**
 * Grupo principal de rutas para el Módulo.
 * Prefijo de URL: '/internal/sales'
 * Prefijo de Nombre de Ruta: 'internal.sales.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-sales,staff',
])->prefix('internal/sales')->name('internal.sales.')->group(
    function (): void {
        /**
         * Muestra el panel principal del Módulo.
         * URL: /internal/sales
         * Nombre de Ruta: internal.sales.index
         */
        Route::get(
            '/',
            [SalesPanelController::class, 'showModulePanel']
        )->name('index');

        // Órdenes: listado y detalle
        Route::get(
            '/orders',
            [OrdersPageController::class, 'list']
        )->name('orders.list');

        Route::get(
            '/orders/{order}',
            [OrdersPageController::class, 'detail']
        )->name('orders.detail');

        // Reportes
        Route::get(
            '/reports/top-products',
            [ReportsPageController::class, 'topProducts']
        )->name('reports.top-products');

        Route::get(
            '/reports/stock-outs',
            [ReportsPageController::class, 'stockOuts']
        )->name('reports.stock-outs');
    }
);
