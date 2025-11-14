<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Module01\App\Http\Controllers\StockMovements\CreateController;
use Modules\Module01\App\Http\Controllers\StockMovements\ListController;

/**
 * Grupo de rutas para la gestión de movimientos de stock (vista de listado).
 * Prefijo de URL: '/internal/module-01/stock-movements'
 * Prefijo de Nombre: 'internal.module01.stock_movements.'
 */
Route::prefix('stock-movements')->name('stock_movements.')->group(function (): void {
    // Lista de movimientos de stock
    Route::get('/', ListController::class)->name('index');

    // Formulario de creación de movimiento de stock
    Route::get('/create', [CreateController::class, 'show'])->name('create');

    // Almacena un movimiento de stock
    Route::post('/', [CreateController::class, 'store'])->name('store');
});
