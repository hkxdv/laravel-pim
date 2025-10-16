<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StaffUsersLoginInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para gestionar la confianza de los dispositivos de inicio de sesión.
 *
 * Se encarga de procesar la solicitud para marcar un dispositivo como confiable,
 * generalmente a través de un enlace firmado enviado por correo electrónico.
 */
final class LoginDeviceController extends Controller
{
    /**
     * Marca un dispositivo como confiable a través de un enlace firmado.
     *
     * Este método utiliza Route Model Binding para inyectar automáticamente la instancia
     * de `StaffUsersLoginInfo` correspondiente al ID en la URL. La ruta que invoca
     * este método debe estar protegida por el middleware 'signed' para prevenir ataques
     * de manipulación de URL y 'auth' para asegurar que el usuario esté autenticado.
     *
     * @param  Request  $request  La solicitud HTTP entrante.
     * @param  StaffUsersLoginInfo  $loginInfo  El registro de inicio de sesión inyectado por la ruta.
     */
    public function trust(Request $request, StaffUsersLoginInfo $loginInfo): RedirectResponse
    {
        /** @var \App\Models\StaffUsers $user */
        $user = $request->user();

        // Comprobación de autorización crucial: se asegura de que el usuario autenticado
        // sea el propietario del registro de inicio de sesión que intenta modificar.
        // Si no lo es, la solicitud se aborta con un código de estado 403 (Prohibido).
        abort_if($user->id !== $loginInfo->staff_user_id, 403, 'No tienes permiso para realizar esta acción.');

        // Si la autorización es exitosa, se actualiza el campo `is_trusted` a `true`.
        $loginInfo->update(['is_trusted' => true]);

        // Finalmente, se redirige al usuario al dashboard con un mensaje de estado
        // que confirma que la operación fue exitosa.
        return to_route('internal.dashboard')
            ->with('status', 'Dispositivo marcado como confiable. Ya no recibirás alertas cuando inicies sesión desde este dispositivo.');
    }
}
