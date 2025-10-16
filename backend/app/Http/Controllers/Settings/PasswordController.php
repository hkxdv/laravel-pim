<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración de la contraseña del usuario.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/password', [
            'contextualNavItems' => $this->getSettingsNavigationItems(),
        ]);
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var array{current_password:string,password:string,password_confirmation?:string} $validated */
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Actualizar la contraseña y registrar fecha de cambio
        $user = $this->requireStaffUser($request);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ])->save();

        return back();
    }
}
