<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

final class CheckPermission
{
    /**
     * Maneja una solicitud entrante para verificar permisos de usuario.
     *
     * Este middleware centraliza la lógica de autorización para usuarios del staff
     * y responde adecuadamente a solicitudes web y API (JSON).
     *
     * @param  Request  $request  La solicitud HTTP entrante.
     * @param  Closure(Request): Response  $next  El siguiente middleware en la cadena.
     * @param  string  $permission  El nombre del permiso a verificar.
     * @param  string|null  $guard  El guard de autenticación a utilizar. Si es nulo, se usa el por defecto.
     *
     * @throws UnauthorizedException Si el usuario no tiene permiso y la solicitud no espera JSON.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $permission,
        ?string $guard = null
    ): Response {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            return $this->handleUnauthorized($request, 'notLoggedIn');
        }

        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $authGuard->user();

        if ($this->userHasPermission($user, $permission, $guard)) {
            return $next($request);
        }

        return $this->handleUnauthorized($request, 'forPermissions', [$permission]);
    }

    /**
     * Comprueba si un usuario tiene un permiso determinado.
     *
     * @param  mixed  $user
     */
    private function userHasPermission(
        $user,
        string $permission,
        ?string $guard
    ): bool {
        if ($user instanceof \App\Interfaces\AuthenticatableUser) {
            if ($user->hasPermissionToCross($permission)) {
                return true;
            }

            // Para los roles de super-admin, es crucial pasar el guard correcto.
            if ($user->hasRole(['ADMIN', 'DEV'], $guard)) {
                return true;
            }

            // Fallback a verificación específica de guard si se proporcionó
            return in_array($guard, [null, '', '0'], true)
                ? $user->hasPermissionTo($permission)
                : $user->hasPermissionTo($permission, $guard);
        }

        // Tipos de usuario no compatibles con permisos
        return false;
    }

    /**
     * Maneja la respuesta para una solicitud no autorizada.
     *
     * @param  string  $exceptionType  El tipo de excepción de Spatie a lanzar ('notLoggedIn' o 'forPermissions').
     * @param  array<int, string>  $exceptionArgs  Argumentos para la excepción.
     */
    private function handleUnauthorized(
        Request $request,
        string $exceptionType,
        array $exceptionArgs = []
    ): Response {
        if ($request->expectsJson()) {
            $message = $exceptionType === 'notLoggedIn'
                ? 'No autenticado.'
                : 'No tienes permiso para realizar esta acción.';
            $statusCode = $exceptionType === 'notLoggedIn' ? 401 : 403;

            return response()->json(['message' => $message], $statusCode);
        }

        throw_if($exceptionType === 'notLoggedIn', UnauthorizedException::notLoggedIn());

        throw UnauthorizedException::forPermissions($exceptionArgs);
    }
}
