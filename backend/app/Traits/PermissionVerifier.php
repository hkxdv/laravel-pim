<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait PermissionVerifier
 * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
 *
 * Este trait es un wrapper sobre CrossGuardPermissions para usar en componentes
 * que no tienen acceso directo al usuario autenticado.
 */
trait PermissionVerifier
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
     *
     * @param  string|array<string>  $permissionName
     */
    public function can(string|array $permissionName): bool
    {
        /** @var \App\Interfaces\AuthenticatableUser|\Illuminate\Contracts\Auth\Authenticatable|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Si el usuario implementa el contrato de permisos cross-guard, úsalo.
        // Este camino ya incluye la lógica de super-admin (ADMIN/DEV) y el cacheo.
        if ($user instanceof \App\Interfaces\AuthenticatableUser) {
            if (is_array($permissionName)) {
                // Intentar método optimizado si existe; si no, iterar manualmente.
                if (method_exists($user, 'hasAnyPermissionCross')) {
                    /** @disregard P1013 [hasAnyPermissionCross proviene de CrossGuardPermissions (runtime correcto)] */
                    return (bool) $user->hasAnyPermissionCross($permissionName);
                }

                return array_any($permissionName, fn (string $perm): bool => $user->hasPermissionToCross($perm));
            }

            return $user->hasPermissionToCross($permissionName);
        }

        // Fallback a la verificación de permisos nativa de Laravel/Spatie si el trait no está.
        if (is_array($permissionName)) {
            return array_any($permissionName, fn ($perm) => $user->hasPermissionTo($perm));
        }

        // Verificación única
        /** @disregard P1013 [hasPermissionTo proviene de Spatie HasRoles en el modelo de usuario] */
        return (bool) $user->hasPermissionTo($permissionName);
    }
}
