<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ModuleRegistryInterface;
use App\Models\StaffUsers as User;
use Illuminate\Support\Facades\Auth;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Laravel\Module as ModuleInstance;

/**
 * Servicio para la gestión y acceso a módulos del sistema.
 * Proporciona métodos para obtener módulos disponibles y su configuración.
 */
final class ModuleRegistryService implements ModuleRegistryInterface
{
    /**
     * Cache de configuraciones de módulos para evitar lecturas repetidas.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configCache = [];

    /**
     * Obtiene los módulos disponibles para un usuario específico.
     *
     * @return list<ModuleInstance>
     */
    public function getAvailableModulesForUser(User $user): array
    {
        // Usar collecciones para aprovechar funciones de orden superior
        return array_values(
            collect(Module::allEnabled())
                ->filter(fn ($module): bool => $module instanceof ModuleInstance
                    && $this->canUserAccessModule($user, $module))
                ->values()
                ->all()
        );
    }

    /**
     * Obtiene los módulos accesibles basados en el usuario actual o todos si no se proporciona usuario.
     *
     * @param  User|null  $user  Usuario para el que se consultan los módulos (o null para todos)
     * @return list<ModuleInstance>
     */
    public function getAccessibleModules(
        ?User $user = null
    ): array {
        // Si no se proporciona usuario, intentar obtener el usuario autenticado desde cualquier guard
        if (! $user instanceof User) {
            $guards = config('auth.guards', []);
            $guardsArr = is_array($guards) ? $guards : [];
            foreach (array_keys($guardsArr) as $guardName) {
                $guard = is_string($guardName) ? $guardName : (string) $guardName;
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            }
        }

        // Si hay un usuario autenticado, filtrar por sus permisos
        if ($user) {
            return $this->getAvailableModulesForUser($user);
        }

        // Si no hay usuario, devolver todos los módulos habilitados
        return array_values(
            array_filter(
                Module::allEnabled(),
                fn ($m): bool => $m instanceof ModuleInstance
            )
        );
    }

    /**
     * Obtiene todos los módulos habilitados sin filtrar por usuario.
     *
     * @return list<ModuleInstance>
     */
    public function getAllEnabledModules(): array
    {
        return array_values(
            array_filter(
                Module::allEnabled(),
                fn ($m): bool => $m instanceof ModuleInstance
            )
        );
    }

    /**
     * Obtiene la configuración de un módulo específico por su nombre.
     * Implementa caché para evitar lecturas repetidas de configuración.
     *
     * @return array<string, mixed>
     */
    public function getModuleConfig(string $moduleName): array
    {
        $moduleSlug = mb_strtolower($moduleName);

        // Si ya tenemos la configuración en caché, devolverla
        if (isset($this->configCache[$moduleSlug])) {
            return $this->configCache[$moduleSlug];
        }

        // Obtener la configuración y guardarla en caché
        $configRaw = config($moduleSlug, []);
        $config = (array) $configRaw;
        /** @var array<string, mixed> $config */
        $this->configCache[$moduleSlug] = $config;

        return $config;
    }

    /**
     * Limpia la caché de configuraciones de módulos.
     */
    public function clearConfigCache(): void
    {
        $this->configCache = [];
    }

    /**
     * Obtiene los ítems de navegación global disponibles para un usuario.
     *
     * @param  User|null  $user  Usuario autenticado
     * @return array<int, array<string, mixed>>
     */
    public function getGlobalNavItems(?User $user = null): array
    {
        // Configuración base para los ítems de navegación global
        return [
            [
                'title' => 'Perfil',
                'route_name' => 'internal.settings.profile.edit',
                'icon' => 'UserCog',
                'permission' => null,
            ],
            [
                'title' => 'Contraseña',
                'route_name' => 'internal.settings.password.edit',
                'icon' => 'KeyRound',
                'permission' => null,
            ],
            [
                'title' => 'Apariencia',
                'route_name' => 'internal.settings.appearance',
                'icon' => 'Palette',
                'permission' => null,
            ],
        ];
    }

    /**
     * Determina si un usuario puede acceder a un módulo específico.
     */
    private function canUserAccessModule(
        User $user,
        ModuleInstance $module
    ): bool {
        $config = $this->getModuleConfig($module->getName());

        // Si no hay configuración, no permitir acceso
        if ($config === []) {
            return false;
        }

        $permission = $config['base_permission'] ?? null;
        $permissionStr = is_string($permission) ? $permission : null;
        $authGuardVal = $config['auth_guard'] ?? null;
        $authGuardStr = is_string($authGuardVal) ? $authGuardVal : null;

        // Si el guard del módulo no coincide con el del usuario, denegar acceso.
        if ($authGuardStr && $user->getAuthGuard() !== $authGuardStr) {
            return false;
        }

        // ADMIN y DEV (del guard 'staff') tienen acceso a todo.
        if (
            $user->getAuthGuard() === 'staff'
            && ($user->hasRole('ADMIN') || $user->hasRole('DEV'))
        ) {
            return true;
        }

        // Si no se requiere permiso, permitir acceso.
        if ($permissionStr === null) {
            return true;
        }

        // Preferir verificación entre guards usando método del contrato.
        return $user->hasPermissionToCross($permissionStr);
    }
}
