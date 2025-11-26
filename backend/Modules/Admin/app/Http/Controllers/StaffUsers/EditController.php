<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AdminBaseController;
use Modules\Admin\App\Http\Requests\UserRequest;

/**
 * Controlador para la edición de usuarios del personal administrativo.
 */
final class EditController extends AdminBaseController
{
    /**
     * Muestra el formulario de edición de un usuario existente.
     *
     * @param  Request  $request  Solicitud HTTP
     * @param  int  $id  ID del usuario a editar
     * @return InertiaResponse Respuesta Inertia con el formulario de edición
     */
    public function show(Request $request, int $id): InertiaResponse
    {
        // Obtener el usuario por ID con sus roles
        $user = $this->staffUserManager->getUserById($id);

        abort_unless($user instanceof \App\Models\StaffUsers, 404, 'Usuario no encontrado');

        // Obtener todos los roles disponibles
        $roles = $this->staffUserManager->getAllRoles();

        // Proporcionar datos adicionales específicos de la vista
        $additionalData = [
            'user' => $user,
            'roles' => $roles,
        ];

        return $this->prepareAndRenderModuleView(
            view: 'user/edit',
            request: $request,
            additionalData: $additionalData
        );
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param  UserRequest  $request  Solicitud validada para actualización de usuario
     * @param  int  $id  ID del usuario a actualizar
     * @return RedirectResponse Redirección con mensaje de éxito
     */
    public function update(UserRequest $request, int $id): RedirectResponse
    {
        try {
            $user = $this->staffUserManager->getUserById($id);

            if (! $user instanceof \App\Models\StaffUsers) {
                return to_route('internal.admin.users.index')
                    ->with(
                        'error',
                        'Usuario no encontrado. No se pudo realizar la actualización.'
                    );
            }

            /** @var array<string, mixed> $validatedData */
            $validatedData = $request->validated();

            // Solo actualizar la contraseña si se proporciona una nueva
            if (empty($validatedData['password'])) {
                unset($validatedData['password']);
            } else {
                $pwdRaw = $validatedData['password'] ?? '';
                $pwd = is_string($pwdRaw) ? $pwdRaw : '';
                $validatedData['password'] = bcrypt($pwd);
                // Registrar fecha de cambio de contraseña
                $validatedData['password_changed_at'] = now();
            }

            $this->staffUserManager->updateUser($id, $validatedData);

            if ($request->has('roles')) {
                $rolesInput = $request->input('roles', []);
                $rolesNormalized = [];
                if (is_array($rolesInput)) {
                    foreach ($rolesInput as $r) {
                        if (is_int($r) || is_string($r) || $r instanceof \Spatie\Permission\Models\Role) {
                            $rolesNormalized[] = $r;
                        }
                    }
                }

                $this->staffUserManager->syncRoles($user, $rolesNormalized);
            }

            $userNameRaw = $user->getAttribute('name');
            $userName = is_string($userNameRaw) ? $userNameRaw : '';

            return to_route('internal.admin.users.index')
                ->with(
                    'success',
                    sprintf("Usuario '%s' actualizado exitosamente.", $userName)
                );
        } catch (Exception $exception) {
            // Loguear el error para análisis posterior
            Log::error(
                'Error al actualizar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $id,
                    'data' => $request->except([
                        'password',
                        'password_confirmation',
                    ]),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            // Mensaje de error amigable para el usuario
            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'Ocurrió un error al actualizar el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }

    /**
     * Elimina un usuario existente.
     *
     * @param  int  $id  ID del usuario a eliminar
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            // Obtener el usuario para verificar si tiene roles protegidos
            $user = $this->staffUserManager->getUserById($id);

            if (! $user instanceof \App\Models\StaffUsers) {
                return to_route('internal.admin.users.index')
                    ->with(
                        'error',
                        'Usuario no encontrado. No se pudo realizar la eliminación.'
                    );
            }

            // Verificar si el usuario tiene roles protegidos
            $hasProtectedRole = $user->roles->contains(function ($role): bool {
                $nameRaw = $role->getAttribute('name');
                $name = is_string($nameRaw) ? $nameRaw : '';

                return in_array(mb_strtoupper($name), ['ADMIN', 'DEV'], true);
            });

            if ($hasProtectedRole) {
                return to_route('internal.admin.users.index')
                    ->with(
                        'error',
                        'No se puede eliminar un usuario con roles protegidos (ADMIN o DEV).'
                    );
            }

            // Proceder con la eliminación si no tiene roles protegidos
            $deleted = $this->staffUserManager->deleteUser($id);

            if ($deleted) {
                $userNameRaw = $user->getAttribute('name');
                $userName = is_string($userNameRaw) ? $userNameRaw : '';

                return to_route('internal.admin.users.index')
                    ->with(
                        'success',
                        sprintf("Usuario '%s' eliminado exitosamente.", $userName)
                    );
            }

            return to_route('internal.admin.users.index')
                ->with(
                    'error',
                    'No se pudo eliminar el usuario. Intente nuevamente.'
                );
        } catch (Exception $exception) {
            // Loguear el error para análisis posterior
            Log::error(
                'Error al eliminar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $id,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return to_route('internal.admin.users.index')
                ->with(
                    'error',
                    'Ocurrió un error al eliminar el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }
}
