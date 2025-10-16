<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interfaz unificada para usuarios autenticables en el sistema.
 *
 * Define un contrato común que deben seguir todos los modelos de usuario
 * (ej. StaffUsers) para interactuar de forma consistente
 * con los servicios de autenticación, permisos y navegación.
 */
interface AuthenticatableUser extends Authenticatable
{
    /**
     * Obtiene el nombre completo o de visualización del usuario.
     */
    public function getDisplayName(): string;

    /**
     * Obtiene el "guard" de autenticación asociado con este tipo de usuario.
     */
    public function getAuthGuard(): string;

    /**
     * Determina si el usuario tiene un rol específico.
     *
     * @param  string|string[]  $roles
     */
    public function hasRole(
        string|array $roles,
        ?string $guardName = null
    ): bool;

    /**
     * Determina si el usuario tiene un permiso específico.
     */
    public function hasPermissionTo(
        string $permission,
        ?string $guardName = null
    ): bool;

    /**
     * Determina si el usuario tiene un permiso específico de forma transversal entre guards.
     */
    public function hasPermissionToCross(string $permission): bool;
}
