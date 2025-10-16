<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API Pública
|--------------------------------------------------------------------------
|
| Este archivo define las rutas para la API pública de la aplicación.
| Todas las rutas aquí definidas son agrupadas bajo el middleware 'api'
| y tienen un prefijo '/api' automáticamente aplicado por Laravel.
|
*/

// Grupo de rutas para la API con limitador de peticiones global.
Route::middleware(['throttle:api', 'api'])->group(function (): void {

    /**
     * Endpoint para la autenticación de usuarios y generación de tokens Sanctum.
     * Utiliza un limitador de peticiones específico para prevenir ataques de fuerza bruta.
     *
     * POST /api/sanctum/token
     */
    Route::post(
        '/sanctum/token',
        [ApiAuthController::class, 'login']
    )->middleware('throttle:6,1')->name('api.login');

    /**
     * Endpoint para obtener la información del usuario autenticado.
     * Requiere un token de Sanctum válido para la autenticación.
     *
     * GET /api/user
     */
    Route::middleware('auth:sanctum')->get(
        '/user',
        [ApiAuthController::class, 'user']
    )->name('api.user');
});

/**
 * Endpoint de Health Check.
 * Utilizado por servicios de monitoreo para verificar que la aplicación está en línea y funcionando.
 *
 * GET /api/health
 */
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]))->name('api.health');
