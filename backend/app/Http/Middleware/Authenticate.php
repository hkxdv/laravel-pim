<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class Authenticate extends Middleware
{
    /**
     * Handle an unauthenticated user.
     *
     * @param  Request  $request
     * @param  list<string>  $guards
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards): void
    {
        // Log del intento de acceso no autorizado para auditoría
        Log::info('Intento de acceso no autorizado', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'guards' => $guards,
        ]);

        parent::unauthenticated($request, $guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // La lógica de redirección principal está en bootstrap/app.php
        // Este método actúa como fallback adicional

        if ($request->expectsJson()) {
            return null;
        }

        // Fallback basado en la ruta actual
        if ($request->is('internal/*')) {
            return route('login');
        }

        return route('welcome');
    }
}
