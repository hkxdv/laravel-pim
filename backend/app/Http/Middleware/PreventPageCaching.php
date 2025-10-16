<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para prevenir el almacenamiento en caché de páginas sensibles.
 *
 * Este middleware es especialmente útil para páginas que contienen información
 * sensible como dashboards, configuraciones de usuario, o datos personales.
 */
final class PreventPageCaching
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo aplicar a respuestas HTML exitosas
        if ($response->isSuccessful() && ! $request->expectsJson()) {
            // Prevenir almacenamiento en caché del navegador
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            // Prevenir almacenamiento en caché de proxies
            $response->headers->set('Surrogate-Control', 'no-store');

            // Prevenir que la página se almacene en el historial del navegador
            // cuando el usuario navega hacia atrás
            $response->headers->set(
                'Cache-Control',
                $response->headers->get('Cache-Control').', no-store'
            );
        }

        return $response;
    }
}
