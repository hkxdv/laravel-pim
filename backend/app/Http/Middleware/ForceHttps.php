<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para forzar HTTPS en producción.
 *
 * Redirige automáticamente las solicitudes HTTP a HTTPS cuando
 * la aplicación está en modo de producción.
 */
final class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo forzar HTTPS en producción
        if (! app()->isProduction()) {
            return $next($request);
        }

        // Si ya es HTTPS, continuar
        if ($request->isSecure()) {
            return $next($request);
        }

        // Verificar si estamos detrás de un proxy que maneja SSL
        if ($this->isBehindSslProxy($request)) {
            return $next($request);
        }

        // Para solicitudes AJAX/API, devolver error en lugar de redirección
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'HTTPS requerido en producción.',
                'error' => 'https_required',
            ], 426); // 426 Upgrade Required
        }

        // Redirigir a HTTPS
        $httpsUrl = 'https://'.$request->getHost().$request->getRequestUri();

        return redirect($httpsUrl, 301);
    }

    /**
     * Verifica si estamos detrás de un proxy SSL (como un load balancer).
     */
    private function isBehindSslProxy(Request $request): bool
    {
        // Verificar cabeceras comunes de proxies SSL
        $sslHeaders = [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_SSL' => 'on',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_CLOUDFRONT_FORWARDED_PROTO' => 'https',
        ];

        return array_any($sslHeaders, fn ($value, $header): bool => $request->server($header) === $value);
    }
}
