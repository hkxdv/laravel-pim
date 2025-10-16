<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar el inicio del flujo de restablecimiento de contraseña.
 *
 * Se encarga de mostrar el formulario para solicitar el enlace y de procesar
 * dicha solicitud, enviando el correo electrónico correspondiente.
 */
final class PasswordResetLinkController extends Controller
{
    /**
     * Muestra la vista para solicitar el enlace de restablecimiento de contraseña.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Maneja la solicitud entrante para enviar el enlace de restablecimiento.
     *
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Valida que se haya proporcionado una dirección de correo electrónico válida.
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Utiliza la fachada Password de Laravel para enviar el enlace de restablecimiento.
        // Esta fachada se encarga de generar el token y enviar la notificación por correo.
        Password::sendResetLink(
            $request->only('email')
        );

        // Devuelve siempre una respuesta genérica para evitar la enumeración de correos.
        // Esto es una medida de seguridad para no revelar si una dirección de correo
        // electrónico está registrada en el sistema o no.
        return back()->with('status', __('Se enviará un enlace de restablecimiento si la cuenta existe.'));
    }
}
