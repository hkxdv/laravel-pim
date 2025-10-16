<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para asegurar que el usuario autenticado esté activo.
 *
 * Verifica que el usuario no haya sido deshabilitado o suspendido
 * después de la autenticación inicial.
 */
final class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        ?string $guard = null
    ): Response {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            return $this->handleUnauthenticated($request);
        }

        $user = $authGuard->user();
        if ($user === null) {
            return $this->handleUnauthenticated($request);
        }

        /** @var \App\Models\StaffUsers $user */
        // Verificar si el usuario está activo
        if (! $user->isActive()) {
            return $this->handleInactiveUser($request, $guard);
        }

        // Verificar si el usuario ha sido eliminado (soft delete)
        if ($user->trashed()) {
            return $this->handleInactiveUser($request, $guard);
        }

        return $next($request);
    }

    /**
     * Maneja el caso cuando el usuario no está autenticado.
     */
    private function handleUnauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No autenticado.',
                'error' => 'unauthenticated',
            ], 401);
        }

        return redirect()->guest(route('welcome'));
    }

    /**
     * Maneja el caso cuando el usuario está inactivo.
     */
    private function handleInactiveUser(
        Request $request,
        ?string $guard
    ): Response {
        // Cerrar la sesión del usuario inactivo
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
                'error' => 'account_inactive',
            ], 403);
        }

        // Redirigir según el guard
        $redirectRoute = match ($guard) {
            'staff' => 'login',
            default => 'welcome'
        };

        return to_route($redirectRoute)
            ->withErrors([
                'email' => 'Tu cuenta ha sido desactivada. Contacta al administrador para más información.',
            ]);
    }
}
