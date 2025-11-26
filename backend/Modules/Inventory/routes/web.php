<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/inventory' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Inventory\App\Http\Controllers\InventoryPanelController;

/**
 * Grupo principal de rutas.
 * Prefijo de URL: '/internal/inventory'
 * Prefijo de Nombre de Ruta: 'internal.inventory.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-inventory,staff',
])->prefix('internal/inventory')->name('internal.inventory.')->group(
    function (): void {

        Route::get(
            '/',
            [InventoryPanelController::class, 'showModulePanel']
        )->name('index');

        // Rutas para la gestión de productos
        require __DIR__.'/products.php';
        // Rutas para la gestión de movimientos de stock
        require __DIR__.'/stock_movements.php';
    }
);
