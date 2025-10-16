<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginDeviceController;
use App\Http\Controllers\InternalDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Internas
|--------------------------------------------------------------------------
|
| Grupo de rutas para el panel de administración. Todas están prefijadas
| con '/internal'.
|
*/

// Grupo de rutas de autenticación internas: prefijo de URI 'internal' pero SIN prefijo de nombre
Route::prefix('internal')->group(function (): void {
    require __DIR__.'/auth.php';
});

// Grupo de rutas internas con prefijo de nombre 'internal.'
Route::prefix('internal')->name('internal.')->group(function (): void {
    /**
     * Grupo de rutas que requieren que el usuario del personal esté
     * autenticado y haya verificado su correo electrónico.
     */
    Route::middleware([
        'auth:staff',
        'verified',
        'throttle:60,1',
    ])->group(function (): void {
        /**
         * Panel de control principal.
         * GET /internal/dashboard
         */
        Route::get(
            '/dashboard',
            [InternalDashboardController::class, 'index']
        )->name('dashboard');

        /**
         * Marca un dispositivo como confiable a través de un enlace firmado.
         * GET /internal/trust-device/{id}
         */
        Route::get(
            '/trust-device/{loginInfo}',
            [LoginDeviceController::class, 'trust']
        )->middleware('signed')->name('login.trust-device');
    });
});
