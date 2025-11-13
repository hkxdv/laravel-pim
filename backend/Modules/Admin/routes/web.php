<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo de Administración
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas para el panel de administración y la gestión
| de usuarios del personal (Staff). Todas las rutas están protegidas por
| el guard 'staff' y permisos específicos.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminPanelController;

Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-admin,staff',
])->name('internal.admin.')->prefix('internal/admin')->group(
    function (): void {

        // La ruta principal del panel de administración.
        Route::get(
            '/',
            [AdminPanelController::class, 'showModulePanel']
        )->name('panel');

        // Rutas para la gestión de usuarios (CRUD de vistas).
        require __DIR__.'/users.php';
    }
);
