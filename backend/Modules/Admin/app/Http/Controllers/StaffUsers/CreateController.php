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
 * Controlador para la creación de usuarios del personal administrativo.
 */
final class CreateController extends AdminBaseController
{
    /**
     * Muestra el formulario de creación de un nuevo usuario.
     *
     * @param  Request  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con el formulario de creación
     */
    public function show(Request $request): InertiaResponse
    {
        // Obtener todos los roles para el formulario
        $roles = $this->staffUserManager->getAllRoles();

        // Proporcionar datos adicionales específicos de la vista
        $additionalData = [
            'roles' => $roles,
        ];

        return $this->prepareAndRenderModuleView(
            view: 'user/create',
            request: $request,
            additionalData: $additionalData
        );
    }

    /**
     * Almacena un nuevo usuario.
     *
     * @param  UserRequest  $request  Solicitud validada para creación de usuario
     * @return RedirectResponse|InertiaResponse Redirección o respuesta Inertia
     */
    public function store(UserRequest $request)
    {
        try {
            /** @var array<string, mixed> $validatedData */
            $validatedData = $request->validated();
            $pwdRaw = $validatedData['password'] ?? '';
            $pwd = is_string($pwdRaw) ? $pwdRaw : '';
            $validatedData['password'] = bcrypt($pwd);
            // Registrar fecha de establecimiento inicial de la contraseña
            $validatedData['password_changed_at'] = now();
            $user = $this->staffUserManager->createUser($validatedData);

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

            // Si es una solicitud de Inertia, devolver una respuesta Inertia en lugar de redireccionar
            if (is_string($request->header('X-Inertia'))) {
                // Actualizar la sesión flash manualmente
                session()->flash(
                    'success',
                    sprintf("Usuario '%s' creado exitosamente.", $user->name)
                );

                // Obtener todos los roles para el formulario
                $roles = $this->staffUserManager->getAllRoles();

                // Datos adicionales específicos para esta vista
                $additionalData = [
                    'roles' => $roles,
                    'user' => $user,
                    'preventRedirect' => true, // Propiedad personalizada para evitar la redirección
                ];

                // Usar el método estándar para preparar y renderizar la vista
                return $this->prepareAndRenderModuleView(
                    view: 'user/create',
                    request: $request,
                    additionalData: $additionalData
                );
            }

            // Para solicitudes normales, redirigir como antes
            $userNameRaw = $user->getAttribute('name');
            $userName = is_string($userNameRaw) ? $userNameRaw : '';

            return to_route('internal.admin.users.index')
                ->with(
                    'success',
                    sprintf("Usuario '%s' creado exitosamente.", $userName)
                );
        } catch (Exception $exception) {
            // Loguear el error para análisis posterior
            Log::error(
                'Error al crear usuario: '.$exception->getMessage(),
                [
                    'data' => $request->except(['password', 'password_confirmation']),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            // Si es una solicitud de Inertia, devolver una respuesta Inertia con errores
            if (is_string($request->header('X-Inertia'))) {
                session()->flash(
                    'error',
                    'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.'
                );

                // Obtener todos los roles para el formulario
                $roles = $this->staffUserManager->getAllRoles();

                // Datos adicionales específicos para esta vista con errores
                $additionalData = [
                    'roles' => $roles,
                    'errors' => [
                        'general' => 'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.',
                    ],
                ];

                // Usar el método estándar para preparar y renderizar la vista
                return $this->prepareAndRenderModuleView(
                    view: 'user/create',
                    request: $request,
                    additionalData: $additionalData
                );
            }

            // Mensaje de error amigable para el usuario en solicitudes normales
            return back()
                ->withInput(
                    $request->except(['password', 'password_confirmation'])
                )
                ->with(
                    'error',
                    'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }
}
