<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AdminBaseController;

/**
 * Controlador para la gestión de listado de usuarios del personal administrativo.
 */
final class ListController extends AdminBaseController
{
    /**
     * Muestra la lista de usuarios.
     *
     * @param  Request  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con la lista de usuarios
     */
    public function __invoke(Request $request): InertiaResponse
    {
        $params = [
            'search' => is_string($request->input('search')) ? $request->input('search') : '',
            'role' => is_string($request->input('role')) ? $request->input('role') : '',
            'sort_field' => is_string($request->input('sort_field')) ? $request->input('sort_field') : 'created_at',
            'sort_direction' => is_string($request->input('sort_direction')) ? $request->input('sort_direction') : 'desc',
            'per_page' => is_numeric($request->input('per_page')) ? (int) $request->input('per_page') : 10,
        ];

        // Datos adicionales específicos para la vista de lista
        $additionalData = [
            'users' => $this->staffUserManager->getAllUsers($params),
            'roles' => $this->staffUserManager->getAllRoles(),
            'filters' => $request->only([
                'search',
                'role',
                'sort_field',
                'sort_direction',
            ]),
        ];

        return $this->prepareAndRenderModuleView(
            view: 'user/list',
            request: $request,
            additionalData: $additionalData
        );
    }
}
