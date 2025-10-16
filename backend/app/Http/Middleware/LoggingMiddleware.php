<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para controlar el logging y reducir verbosidad en producción.
 */
final class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // En producción, reducir el nivel de logging para rutas comunes
        if (app()->environment('production')) {
            $this->configureProductionLogging($request);
        }

        $response = $next($request);

        // Log de errores en producción
        if (app()->environment('production') && $response->getStatusCode() >= 400) {
            Log::error('Error HTTP', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    /**
     * Configura el logging para producción.
     */
    private function configureProductionLogging(Request $request): void
    {
        // Rutas que no necesitan logging detallado en producción
        $quietRoutes = [
            'internal.settings.profile.edit',
            'internal.settings.password.edit',
            'internal.settings.appearance',
            'internal.admin.panel',
            'internal.module01.index',
            'internal.module02.index',
            'internal.module03.index',
            'internal.module04.index',
        ];

        $currentRoute = $request->route()?->getName();

        // Si es una ruta silenciosa, configurar logging mínimo
        if (in_array($currentRoute, $quietRoutes)) {
            // Configurar temporalmente el nivel de logging para esta request
            config(['logging.channels.single.level' => 'warning']);
        }
    }
}
