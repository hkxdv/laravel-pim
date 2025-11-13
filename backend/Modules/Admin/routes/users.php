<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\StaffUsers\CreateController;
use Modules\Admin\App\Http\Controllers\StaffUsers\EditController;
use Modules\Admin\App\Http\Controllers\StaffUsers\ListController;

/**
 * Grupo de rutas para la gestión de usuarios (CRUD de vistas).
 * Prefijo de URL: '/internal/admin/users'
 * Prefijo de Nombre: 'internal.admin.users.'
 */
Route::prefix('users')->name('users.')->group(
    function (): void {
        // Muestra la lista de usuarios.
        Route::get('/', ListController::class)->name('index');

        // Muestra el formulario para crear un nuevo usuario.
        Route::get('/create', [
            CreateController::class,
            'show',
        ])->name('create');

        // Almacena el nuevo usuario.
        Route::post('/', [
            CreateController::class,
            'store',
        ])->name('store');

        // Muestra el formulario para editar un usuario existente.
        Route::get('/{user}/edit', [
            EditController::class,
            'show',
        ])->name('edit');

        // Actualiza el usuario existente.
        Route::put('/{user}', [
            EditController::class,
            'update',
        ])->name('update');

        // Elimina el usuario.
        Route::delete('/{user}', [
            EditController::class,
            'destroy',
        ])->name('destroy');
    }
);
