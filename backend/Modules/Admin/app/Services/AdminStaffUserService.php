<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use App\Models\StaffUsers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Spatie\Permission\Models\Role;

/**
 * Servicio para manejar la lógica de negocio de la gestión de usuarios del personal (Staff).
 * Implementa las operaciones definidas en la interfaz StaffUserManagerInterface.
 */
final class AdminStaffUserService implements StaffUserManagerInterface
{
    /**
     * Devuelve todos los usuarios con sus roles.
     *
     * @param  array<string, mixed>  $params  Parámetros para filtrado y ordenación
     * @param  int  $perPage  Número de elementos por página
     */
    public function getAllUsers(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = StaffUsers::query()
            // Eliminamos 'avatar' del select porque es un atributo computado
            ->select('id', 'name', 'email', 'created_at', 'updated_at')
            // Ya no existe la relación contactProfile
            ->with(['roles']);

        // Filtrado por término de búsqueda
        if (! empty($params['search'])) {
            $searchTerm = $params['search'];
            $query->where(
                function ($q) use ($searchTerm): void {
                    $q->where('name', 'like', sprintf('%%%s%%', $searchTerm))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $searchTerm));
                }
            );
        }

        // Filtrado por rol específico
        if (! empty($params['role'])) {
            $query->whereHas(
                'roles',
                function ($q) use ($params): void {
                    $q->where('name', $params['role']);
                }
            );
        }

        // Ordenamiento
        $sortField = $params['sort_field'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';

        // Verificar que el campo de ordenamiento es válido usando la constante de la interfaz
        if (in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Obtener número de elementos por página
        $perPage = $params['per_page'] ?? $perPage;

        // Paginar los resultados
        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo usuario con los datos proporcionados.
     *
     * @param  array<string, mixed>  $data  Datos del nuevo usuario
     * @return StaffUsers Usuario creado
     */
    public function createUser(array $data): StaffUsers
    {
        // Determinar si se debe verificar automáticamente el email (por defecto: true)
        $shouldAutoVerify = ! isset($data['auto_verify_email'])
            || $data['auto_verify_email'] === true;

        if ($shouldAutoVerify) {
            $data['email_verified_at'] = now();
        }

        // Eliminar el campo auto_verify_email antes de crear el usuario
        if (isset($data['auto_verify_email'])) {
            unset($data['auto_verify_email']);
        }

        // Crear el usuario con los datos proporcionados
        $user = StaffUsers::query()->create($data);
        // Inicializar fecha de establecimiento de contraseña
        $user->forceFill([
            'password_changed_at' => now(),
        ])->save();

        // Si no se verificará automáticamente, enviar notificación de verificación
        if (
            ! $shouldAutoVerify
            && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail
        ) {
            $user->sendEmailVerificationNotification();
        }

        // Si se proporcionaron roles, sincronizarlos
        if (isset($data['roles']) && is_array($data['roles'])) {
            $this->syncRoles($user, $data['roles']);
        }

        return $user;
    }

    /**
     * Obtiene un usuario por su ID.
     *
     * @param  int  $id  ID del usuario
     * @return StaffUsers|null Usuario encontrado o null
     */
    public function getUserById(int $id): ?StaffUsers
    {
        return StaffUsers::with('roles', 'permissions')->find($id);
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param  int  $id  ID del usuario
     * @param  array<string, mixed>  $data  Datos actualizados
     * @return StaffUsers|null Usuario actualizado o null
     */
    public function updateUser(int $id, array $data): ?StaffUsers
    {
        $user = StaffUsers::query()->find($id);
        if ($user) {
            // Extraer password_changed_at si viene en payload y evitar mass assignment
            $pwdChangedAt = $data['password_changed_at'] ?? null;
            if (array_key_exists('password_changed_at', $data)) {
                unset($data['password_changed_at']);
            }

            $user->update($data);

            if ($pwdChangedAt !== null) {
                $user->forceFill([
                    'password_changed_at' => $pwdChangedAt,
                ])->save();
            }
        }

        return $user;
    }

    /**
     * Elimina un usuario por su ID.
     *
     * @param  int  $id  ID del usuario
     * @return bool Éxito de la operación
     */
    public function deleteUser(int $id): bool
    {
        $user = StaffUsers::query()->find($id);
        if ($user) {
            return (bool) $user->delete();
        }

        return false;
    }

    /**
     * Sincroniza los roles de un usuario, preservando los roles protegidos.
     * Los roles protegidos (ADMIN y DEV) no pueden ser eliminados si ya están asignados.
     *
     * @param  StaffUsers  $user  Usuario a actualizar
     * @param  array<string|int|Role>  $roles  Roles a asignar
     */
    public function syncRoles(StaffUsers $user, array $roles): void
    {
        // 1. Filtrar los roles 'ADMIN' y 'DEV' de la solicitud.
        $assignableRoles = collect($roles)->filter(function ($role): bool {
            $roleName = is_string($role)
                ? $role
                : ($role instanceof Role
                    ? $role->name
                    : null
                );

            return ! in_array(
                mb_strtoupper((string) $roleName),
                ['ADMIN', 'DEV'],
                true
            );
        })->all();

        // 2. Obtener los roles protegidos que el usuario ya tiene.
        $protectedRoles = $user->roles->filter(
            fn ($role): bool => in_array(
                mb_strtoupper((string) $role->name),
                ['ADMIN', 'DEV'],
                true
            )
        )->pluck('name')->all();

        // 3. Unir los roles asignables con los protegidos existentes.
        $finalRoles = array_unique(
            array_merge($assignableRoles, $protectedRoles)
        );

        $user->syncRoles($finalRoles);
    }

    /**
     * Obtiene el número total de usuarios registrados en el sistema.
     *
     * @return int Total de usuarios
     */
    public function getTotalUsers(): int
    {
        return StaffUsers::query()->count();
    }

    /**
     * Obtiene el número total de roles definidos en el sistema.
     *
     * @return int Total de roles
     */
    public function getTotalRoles(): int
    {
        return Role::query()->where('guard_name', 'staff')->count();
    }

    /**
     * Obtiene todos los roles disponibles en el sistema.
     *
     * @return Collection<int, Role> Colección de roles
     */
    public function getAllRoles(): Collection
    {
        // Usar el modelo Role directamente con where para obtener una Eloquent\Collection
        $roles = Role::query()->where('guard_name', 'staff')->get([
            'id',
            'name',
            'guard_name',
        ]);

        // Añadir descripción para cada rol
        $roles->each(
            function ($role): void {
                // Asegurarnos de que el ID sea un entero para evitar problemas de tipado en el frontend
                $role->id = (int) $role->id;

                // Añadir una descripción según el nombre del rol
                match (mb_strtoupper((string) $role->name)) {
                    'ADMIN' => $role->setAttribute(
                        'description',
                        'Acceso completo a todas las funciones del sistema'
                    ),
                    'DEV' => $role->setAttribute(
                        'description',
                        'Acceso de desarrollador con privilegios especiales'
                    ),
                    'MOD-01' => $role->setAttribute(
                        'description',
                        'Acceso al Módulo 01'
                    ),
                    'MOD-02' => $role->setAttribute(
                        'description',
                        'Acceso al Módulo 02'
                    ),
                    default => $role->setAttribute(
                        'description',
                        'Rol de '.$role->name
                    ),
                };
            }
        );

        return $roles;
    }
}
