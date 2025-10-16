<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para mostrar el aviso de verificación de correo electrónico.
 *
 * Este controlador de acción única se activa cuando un usuario intenta acceder a una ruta
 * protegida por el middleware 'verified' sin tener su correo verificado. Su única
 * responsabilidad es mostrar la página que le pide al usuario que verifique su correo.
 */
final class EmailVerificationPromptController extends Controller
{
    /**
     * Muestra la página de aviso de verificación de correo o redirige si el usuario ya está verificado.
     *
     * Este método es invocado automáticamente cuando un usuario no verificado intenta acceder
     * a una ruta protegida. Devuelve una vista para verificar o una redirección.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        // Se comprueba si el usuario ya ha verificado su correo electrónico.
        $user = $this->requireStaffUser($request);
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('internal.dashboard', absolute: false));
        }

        // Si el usuario no está verificado, se renderiza la vista de Inertia 'auth/verify-email'.
        // Se pasa el 'status' de la sesión, que puede contener mensajes como 'verification-link-sent'
        // para informar al usuario que se ha enviado un nuevo enlace.
        return Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]);
    }
}
