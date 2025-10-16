<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar la integridad de la sesión.
 *
 * Previene ataques de session hijacking verificando la consistencia
 * de la información de la sesión con cada solicitud.
 */
final class ValidateSessionIntegrity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $authGuard = Auth::guard($guard);

        // Solo validar para usuarios autenticados
        if ($authGuard->guest()) {
            return $next($request);
        }

        $user = $authGuard->user();
        $sessionKey = 'session_integrity_'.$guard;

        // Obtener información actual de la sesión
        $currentFingerprint = $this->generateSessionFingerprint($request);
        /**
         * @var array{
         *   user_agent: string|null,
         *   ip_network: string|null,
         *   accept_language: string|null,
         *   accept_encoding: string|null
         * }|null $storedFingerprint
         */
        $storedFingerprint = $request->session()->get($sessionKey);

        // Si es la primera vez, almacenar el fingerprint
        if (! is_array($storedFingerprint)) {
            $request->session()->put($sessionKey, $currentFingerprint);

            return $next($request);
        }

        // Verificar si el fingerprint ha cambiado sospechosamente
        if (! $this->validateFingerprint($currentFingerprint, $storedFingerprint)) {
            if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
                return $next($request);
            }

            return $this->handleSuspiciousActivity($request, $user, $guard);
        }

        // Actualizar timestamp de última actividad
        $request->session()->put($sessionKey.'_last_activity', now()->timestamp);

        return $next($request);
    }

    /**
     * Genera un fingerprint de la sesión basado en información del cliente.
     *
     * @return array{
     *   user_agent: string|null,
     *   ip_network: string|null,
     *   accept_language: string|null,
     *   accept_encoding: string|null
     * }
     */
    private function generateSessionFingerprint(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip_network' => $this->getIpNetwork($request->ip()),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
        ];
    }

    /**
     * Obtiene la red IP (primeros 3 octetos) para permitir cambios menores de IP.
     */
    private function getIpNetwork(?string $ip): ?string
    {
        if (in_array($ip, [null, '', '0'], true)) {
            return null;
        }

        $parts = explode('.', $ip);
        if (count($parts) >= 3) {
            return implode('.', array_slice($parts, 0, 3)).'.0';
        }

        return $ip;
    }

    /**
     * Valida si el fingerprint actual es consistente con el almacenado.
     *
     * @param array{
     *   user_agent: string|null,
     *   ip_network: string|null,
     *   accept_language: string|null,
     *   accept_encoding: string|null
     * } $current
     * @param array{
     *   user_agent: string|null,
     *   ip_network: string|null,
     *   accept_language: string|null,
     *   accept_encoding: string|null
     * } $stored
     */
    private function validateFingerprint(array $current, array $stored): bool
    {
        // User-Agent debe ser exactamente igual
        if ($current['user_agent'] !== $stored['user_agent']) {
            return false;
        }

        // IP puede cambiar ligeramente (misma red)
        // Permitir cambio de IP solo si otros factores coinciden
        if ($current['ip_network'] !== $stored['ip_network'] && ($current['accept_language'] !== $stored['accept_language'] || $current['accept_encoding'] !== $stored['accept_encoding'])) {
            return false;
        }

        return true;
    }

    /**
     * Maneja actividad sospechosa en la sesión.
     */
    private function handleSuspiciousActivity(
        Request $request,
        \Illuminate\Contracts\Auth\Authenticatable $user,
        ?string $guard
    ): Response {
        // Log de la actividad sospechosa
        Log::warning(
            'Actividad sospechosa detectada en sesión',
            [
                'user_id' => $user->getAuthIdentifier(),
                'guard' => $guard,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]
        );

        // Cerrar la sesión por seguridad
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesión invalidada por motivos de seguridad.',
                'error' => 'session_compromised',
            ], 401);
        }

        // Redirigir según el guard
        $redirectRoute = match ($guard) {
            'staff' => 'login',
            default => 'welcome'
        };

        return to_route($redirectRoute)
            ->withErrors([
                'email' => 'Tu sesión ha sido invalidada por motivos de seguridad. Por favor, inicia sesión nuevamente.',
            ]);
    }
}
