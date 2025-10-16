<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación del Personal (Staff)
|--------------------------------------------------------------------------
|
| Este archivo define todas las rutas relacionadas con el ciclo de vida de
| la autenticación para los usuarios del personal (StaffUsers).
|
| Estas rutas se agrupan automáticamente
| bajo el prefijo 'internal/', por lo que una ruta como 'login' se
| convierte en 'internal/login'.
|
| El registro de nuevos usuarios está deshabilitado intencionadamente.
|
*/

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\Internal\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/**
 * =========================================================================
 * Rutas para Invitados (Usuarios No Autenticados)
 * =========================================================================
 * Estas rutas manejan el flujo para usuarios que aún no han iniciado sesión.
 * Se especifica el guard 'staff' para asegurar que se aplique al personal.
 */
Route::middleware('guest:staff')->group(function (): void {
    /**
     * Muestra el formulario de inicio de sesión para el personal.
     * GET /internal/login
     */
    Route::get(
        'login',
        [LoginController::class, 'create']
    )->name('login');

    /**
     * Procesa la solicitud de inicio de sesión (autentica al usuario).
     * POST /internal/login
     */
    Route::post(
        'login',
        [LoginController::class, 'store']
    )->name('login.store');

    /**
     * Muestra el formulario para solicitar el enlace de reseteo de contraseña.
     * GET /internal/forgot-password
     */
    Route::get(
        'forgot-password',
        [PasswordResetLinkController::class, 'create']
    )->name('password.request');

    /**
     * Procesa la solicitud y envía el enlace de reseteo por correo.
     * POST /internal/forgot-password
     */
    Route::post(
        'forgot-password',
        [PasswordResetLinkController::class, 'store']
    )->name('password.email');

    /**
     * Muestra el formulario para cambiar la contraseña usando un token.
     * GET /internal/reset-password/{token}
     */
    Route::get(
        'reset-password/{token}',
        [NewPasswordController::class, 'create']
    )->name('password.reset');

    /**
     * Procesa la solicitud y actualiza la contraseña del usuario.
     * POST /internal/reset-password
     */
    Route::post(
        'reset-password',
        [NewPasswordController::class, 'store']
    )->name('password.store');
});

/**
 * =========================================================================
 * Rutas para Usuarios Autenticados
 * =========================================================================
 * Rutas que requieren que el usuario del personal ya haya iniciado sesión.
 * Se especifica el guard 'staff' para asegurar que se aplique al personal.
 */
Route::middleware(['auth:staff'])->group(function (): void {
    /**
     * Muestra la pantalla de aviso para verificar el correo electrónico.
     * Se muestra si el usuario no ha verificado su email.
     * GET /internal/verify-email
     */
    Route::get(
        'verify-email',
        EmailVerificationPromptController::class
    )->name('verification.notice');

    /**
     * Procesa el enlace de verificación de correo electrónico.
     * Requiere una URL firmada para seguridad.
     * GET /internal/verify-email/{id}/{hash}
     */
    Route::get(
        'verify-email/{id}/{hash}',
        VerifyEmailController::class
    )->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    /**
     * Reenvía la notificación de verificación de correo electrónico.
     * Limita la frecuencia de reenvíos para evitar spam.
     * POST /internal/email/verification-notification
     */
    Route::post(
        'email/verification-notification',
        [EmailVerificationNotificationController::class, 'store']
    )->middleware('throttle:6,1')->name('verification.send');

    /**
     * Muestra el formulario para confirmar la contraseña.
     * Se usa antes de realizar acciones sensibles en la aplicación.
     * GET /internal/confirm-password
     */
    Route::get(
        'confirm-password',
        [ConfirmablePasswordController::class, 'show']
    )->name('password.confirm');

    /**
     * Procesa la solicitud de confirmación de contraseña.
     * POST /internal/confirm-password
     */
    Route::post(
        'confirm-password',
        [ConfirmablePasswordController::class, 'store']
    )->name('password.confirm.store');

    /**
     * Procesa la solicitud de cierre de sesión del usuario.
     * POST /internal/logout
     */
    Route::post(
        'logout',
        [LoginController::class, 'destroy']
    )->name('logout');
});
