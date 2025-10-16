<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

final class VerifyCsrfToken extends Middleware
{
    /**
     * Las URIs que deben ser excluidas de la verificación CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Solo excluir la ruta para obtener el token CSRF inicial
        // Esta ruta debe estar exenta porque es precisamente la que proporciona
        // el token CSRF para el resto de solicitudes y no puede verificarse a sí misma
        'sanctum/csrf-cookie',

        // Nota: Si alguna ruta muestra errores 419,
        // considere diagnosticar el problema específico en lugar de añadir excepciones.
    ];
}
