<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Ruta principal de bienvenida (redirige a inicio de sesión)
Route::get(
    '/',
    function (): RedirectResponse {
        // Si ya está autenticado en el guard de staff, ir al dashboard interno
        if (Illuminate\Support\Facades\Auth::guard('staff')->check()) {
            return to_route('internal.dashboard');
        }

        // Si no, mostrar login interno
        return to_route('login');
    }
)->name('welcome');

/**
 * Redirige la antigua ruta de registro a la página de inicio de sesión.
 * El registro de personal se maneja internamente.
 * GET /register
 */
Route::get(
    '/register',
    fn (): RedirectResponse => to_route('login')
)->name('register.redirect');

/**
 * Ruta para obtener la cookie CSRF, necesaria para clientes SPA como Vue/React.
 * GET /sanctum/csrf-cookie
 */
Route::get(
    '/sanctum/csrf-cookie',
    fn () => response()->noContent()
)->name('sanctum.csrf-cookie');

/*
|--------------------------------------------------------------------------
| Carga de Archivos de Rutas Adicionales
|--------------------------------------------------------------------------
*/
require __DIR__.'/internal.php';
require __DIR__.'/settings.php';
require __DIR__.'/protect-assets.php';
require __DIR__.'/ziggy-debug.php';
