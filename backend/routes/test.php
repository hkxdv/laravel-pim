<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Rutas de prueba para visualizar errores (solo en desarrollo)
if (app()->environment('local')) {
    Route::prefix('test-errors')->middleware(['web'])->group(function (): void {

        // Ruta para probar todos los tipos de errores
        Route::get(
            '/view/{code}',
            function (int|string $code) {
                $code = (int) $code;
                $message = match ($code) {
                    400 => 'La solicitud contiene errores o no puede ser procesada.',
                    401 => 'No has iniciado sesión o tu sesión ha expirado.',
                    403 => 'No tienes permiso para acceder a esta página.',
                    404 => 'Lo sentimos, la página que buscas no existe.',
                    405 => 'El método de solicitud no está permitido.',
                    408 => 'La solicitud tardó demasiado tiempo en completarse.',
                    419 => 'Tu sesión ha expirado. Por favor, recarga la página e intenta nuevamente.',
                    422 => 'Los datos proporcionados no son válidos.',
                    429 => 'Has realizado demasiadas solicitudes en poco tiempo.',
                    500 => 'Se ha producido un error interno en el servidor.',
                    503 => 'El servicio no está disponible temporalmente.',
                    default => 'Código de error: '.$code,
                };

                return Inertia\Inertia::render('errors/error-page', [
                    'status' => $code,
                    'message' => $message,
                ]);
            }
        )->name('test.error.view')->where('code', '[0-9]+');
    });
}
