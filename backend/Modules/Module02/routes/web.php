<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo 02
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/module-02' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
 */

use Illuminate\Support\Facades\Route;
use Modules\Module02\App\Http\Controllers\Module02PanelController;

/**
 * Grupo principal de rutas para el Módulo 02.
 * Prefijo de URL: '/internal/module-02'
 * Prefijo de Nombre de Ruta: 'internal.module02.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-module-02,staff',
])->prefix('internal/module-02')->name('internal.module02.')->group(
    function () {
        /**
         * Muestra el panel principal del Módulo 02.
         * URL: /internal/module-02
         * Nombre de Ruta: internal.module02.index
         * Controlador: Module02PanelController@showModulePanel
         * Permiso Requerido: access-module-02
         */
        Route::get(
            '/',
            [Module02PanelController::class, 'showModulePanel']
        )->name('index');
    }
);
