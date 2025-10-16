<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar la confirmación de contraseña.
 *
 * Este controlador maneja el flujo de "modo seguro", donde se solicita al usuario
 * que vuelva a introducir su contraseña para realizar acciones sensibles. Una vez
 * confirmada, se almacena una marca de tiempo en la sesión.
 */
final class ConfirmablePasswordController extends Controller
{
    /**
     * Muestra la vista de confirmación de contraseña.
     *
     * Renderiza la página donde el usuario debe introducir su contraseña
     * para continuar con una acción protegida.
     */
    public function show(): Response
    {
        return Inertia::render('auth/confirm-password');
    }

    /**
     * Valida y confirma la contraseña del usuario.
     *
     * Si la contraseña es correcta, se almacena una marca de tiempo en la sesión
     * para indicar que el usuario ha confirmado su identidad recientemente.
     *
     *
     * @throws ValidationException Si la contraseña es incorrecta.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validar entrada de manera tipada
        /** @var array{password:string} $validated */
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $this->requireStaffUser($request);

        // Validar credenciales contra el guard 'staff'
        throw_unless(
            Auth::guard('staff')->validate([
                'email' => $user->email,
                'password' => $validated['password'],
            ]),
            ValidationException::withMessages([
                'password' => __('auth.password'),
            ])
        );

        // Si la contraseña es correcta, se guarda una marca de tiempo en la sesión.
        // Esto activa el "modo seguro" de Laravel por un tiempo determinado.
        $request->session()->put('auth.password_confirmed_at', time());

        // Finalmente, se redirige al usuario a la URL a la que intentaba acceder originalmente.
        return redirect()->intended(route(
            'internal.dashboard',
            absolute: false
        ));
    }
}
