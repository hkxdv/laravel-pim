<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración del perfil del usuario.
     */
    public function edit(Request $request): Response
    {
        $this->requireStaffUser($request);

        return Inertia::render('settings/profile', [
            // El modelo StaffUsers implementa MustVerifyEmail; siempre verdadero
            'mustVerifyEmail' => true,
            'status' => $request->session()->get('status'),
            'contextualNavItems' => $this->getSettingsNavigationItems(),
        ]);
    }

    /**
     * Actualiza la configuración del perfil del usuario.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $this->requireStaffUser($request);
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('internal.settings.profile.edit');
    }

    /**
     * Elimina la cuenta del usuario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $this->requireStaffUser($request);

        FacadesAuth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
