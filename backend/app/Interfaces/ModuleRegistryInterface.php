<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\StaffUsers as User;
use Nwidart\Modules\Laravel\Module;

/**
 * Interfaz para el registro y acceso a módulos del sistema.
 * Define cómo se registran, consultan y gestionan los módulos disponibles.
 */
interface ModuleRegistryInterface
{
    /**
     * Obtiene los módulos disponibles para un usuario específico según sus permisos.
     *
     * @param  User  $user  Usuario para el que se consultan los módulos disponibles
     * @return array<Module> Array de módulos a los que el usuario tiene acceso
     */
    public function getAvailableModulesForUser(User $user): array;

    /**
     * Obtiene los módulos accesibles basados en el usuario actual o todos si no se proporciona usuario.
     *
     * @param  User|null  $user  Usuario para el que se consultan los módulos (o null para todos)
     * @return array<Module> Array de módulos accesibles
     */
    public function getAccessibleModules(?User $user = null): array;

    /**
     * Obtiene todos los módulos habilitados sin filtrar por usuario.
     *
     * @return array<Module> Array de módulos habilitados
     */
    public function getAllEnabledModules(): array;

    /**
     * Obtiene la configuración de un módulo específico por su nombre.
     *
     * @param  string  $moduleName  Nombre del módulo
     * @return array<string, mixed> Configuración del módulo
     */
    public function getModuleConfig(string $moduleName): array;

    /**
     * Limpia la caché de configuraciones de módulos.
     */
    public function clearConfigCache(): void;

    /**
     * Obtiene los ítems de navegación global disponibles para un usuario.
     *
     * @param  User|null  $user  Usuario autenticado
     * @return array<int, array<string, mixed>> Ítems de navegación global
     */
    public function getGlobalNavItems(?User $user = null): array;
}
