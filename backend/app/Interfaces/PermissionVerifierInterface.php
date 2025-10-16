<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interfaz para la verificación de permisos de usuario.
 * Define cómo se verifican los permisos de los usuarios en el sistema.
 */
interface PermissionVerifierInterface
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
     *
     * @param  string|array<string>  $permissionName  Nombre del permiso o lista de permisos
     * @return bool Si el usuario tiene el permiso especificado
     */
    public function can(string|array $permissionName): bool;
}
