<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar el reenvío de notificaciones de verificación de correo.
 *
 * Este controlador se encarga de una única acción: reenviar el correo de verificación
 * si el usuario lo solicita. Esto es útil si el correo original no llegó o si el
 * enlace de verificación ha expirado.
 */
final class EmailVerificationNotificationController extends Controller
{
    /**
     * Envía una nueva notificación de verificación de correo electrónico al usuario.
     *
     * Si el correo del usuario ya ha sido verificado, se le redirige al dashboard.
     * En caso contrario, se envía la notificación y se le redirige a la página
     * anterior con un mensaje de estado que indica que el enlace ha sido enviado.
     */
    public function store(Request $request): RedirectResponse
    {
        // Primero, se comprueba si el correo del usuario ya ha sido verificado.
        $user = $this->requireStaffUser($request);
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('internal.dashboard', absolute: false));
        }

        // Si el correo no está verificado, se invoca el método de Laravel para enviar
        // la notificación de verificación de correo electrónico.
        $user->sendEmailVerificationNotification();

        // Finalmente, se redirige al usuario a la página anterior con un mensaje de estado.
        // El frontend puede usar este estado para mostrar una notificación de éxito.
        return back()->with('status', 'verification-link-sent');
    }
}
