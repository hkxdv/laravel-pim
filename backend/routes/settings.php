<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas de Configuración del Perfil de Usuario (Staff)
|--------------------------------------------------------------------------
|
| Todas las rutas están protegidas y requieren que el usuario esté
| autenticado con el guard 'staff' y haya verificado su correo.
|
*/

use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

/**
 * Grupo de rutas para la configuración del perfil del personal.
 *
 * @prefix /internal/settings
 *
 * @name internal.settings.
 *
 * @middleware web, auth:staff, verified, throttle
 */
Route::prefix('internal/settings')
    ->name('internal.settings.')
    ->middleware(['web', 'auth:staff', 'verified', 'throttle:60,1'])
    ->group(function (): void {
        /**
         * Redirige la ruta base '/internal/settings' a la página de perfil.
         * GET /internal/settings -> /internal/settings/profile
         */
        Route::redirect('/', 'profile')->name('index.redirect');

        // --- Gestión del Perfil ---
        Route::controller(
            ProfileController::class
        )->group(function (): void {
            Route::get('profile', 'edit')->name('profile.edit');
            Route::patch('profile', 'update')->name('profile.update');
            Route::delete('profile', 'destroy')->name('profile.destroy');
        });

        // --- Gestión de Contraseña ---
        Route::controller(
            PasswordController::class
        )->group(function (): void {
            Route::get('password', 'edit')->name('password.edit');
            Route::put('password', 'update')->name('password.update');
        });

        // --- Configuración de Apariencia ---
        Route::controller(
            AppearanceController::class
        )->group(function (): void {
            Route::get('appearance', 'show')->name('appearance');
        });
    });
