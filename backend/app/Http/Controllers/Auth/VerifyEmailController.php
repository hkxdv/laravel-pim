<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador para gestionar la verificación final del correo electrónico.
 *
 * Este controlador de acción única se activa cuando el usuario hace clic en el enlace
 * de verificación. Utiliza EmailVerificationRequest para validar la firma y al usuario,
 * marca el correo como verificado y dispara el evento correspondiente.
 */
final class VerifyEmailController extends Controller
{
    /**
     * Marca la dirección de correo electrónico del usuario autenticado como verificada.
     *
     * Si el correo no ha sido verificado, lo marca como tal y dispara el evento 'Verified'.
     * Finalmente, redirige al usuario al dashboard con un indicador de que la
     * verificación se ha completado.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        /** @var \App\Models\StaffUsers $user */
        $user = $request->user();

        // Si el usuario aún no ha verificado su correo, se procede a marcarlo.
        if (! $user->hasVerifiedEmail()) {
            // Marca el correo electrónico como verificado en la base de datos.
            $user->markEmailAsVerified();

            // Dispara el evento 'Verified' para que otros listeners puedan reaccionar.
            event(new Verified($user));
        }

        // Redirige al usuario al dashboard, añadiendo un parámetro para que el frontend
        // pueda mostrar un mensaje de bienvenida o de estado.
        return redirect()->intended(route('internal.dashboard', absolute: false).'?verified=1');
    }
}
