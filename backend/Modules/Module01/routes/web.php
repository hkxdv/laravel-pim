<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo 01
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/module-01' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Module01\App\Http\Controllers\Module01PanelController;

/**
 * Grupo principal de rutas para el Módulo 01.
 * Prefijo de URL: '/internal/module-01'
 * Prefijo de Nombre de Ruta: 'internal.module01.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-module-01,staff',
])->prefix('internal/module-01')->name('internal.module01.')->group(
    function (): void {
        /**
         * Muestra el panel principal del Módulo 01.
         * URL: /internal/module-01
         * Nombre de Ruta: internal.module01.index
         * Controlador: Module01Controller@showModulePanel
         * Permiso Requerido: access-module-01
         */
        Route::get(
            '/',
            [Module01PanelController::class, 'showModulePanel']
        )->name('index');

        // Rutas para la gestión de productos (CRUD de vistas)
        require __DIR__.'/products.php';
        // Rutas para la gestión de movimientos de stock (listado)
        require __DIR__.'/stock_movements.php';
    }
);
