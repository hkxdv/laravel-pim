<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para gestionar la autenticación de la API.
 *
 * Maneja el inicio de sesión de usuarios y la obtención de datos del usuario autenticado
 * a través de tokens de API de Sanctum.
 */
final class ApiAuthController extends Controller
{
    /**
     * Maneja la solicitud de inicio de sesión y genera un token de API.
     *
     * Valida las credenciales del usuario, verifica el estado de su correo electrónico
     * y, si todo es correcto, emite un nuevo token de Sanctum. El `device_name`
     * se utiliza para identificar el token y revocar tokens antiguos del mismo dispositivo.
     *
     * @param  Request  $request  La solicitud HTTP con las credenciales.
     * @return JsonResponse La respuesta JSON con el token de acceso.
     *
     * @throws ValidationException Si la validación falla.
     */
    public function login(Request $request): JsonResponse
    {
        /**
         * @var array{email:string,password:string,device_name:string} $data
         */
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $email = $data['email'];
        $password = $data['password'];
        $deviceName = $data['device_name'];

        $user = StaffUsers::query()->where('email', $email)->first();

        $hashedPassword = $user?->password;
        throw_if(
            ! $user || ! is_string($hashedPassword) || ! Hash::check(
                $password,
                $hashedPassword
            ),
            ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ])
        );

        throw_if(
            ! $user->hasVerifiedEmail(),
            ValidationException::withMessages([
                'email' => ['Debes verificar tu correo electrónico antes de continuar.'],
            ])
        );

        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName, ['basic'], now()->addDays(30));

        Log::info('Token API generado', [
            'user_id' => $user->getAuthIdentifier(),
            'device' => $deviceName,
            'ip' => $request->ip(),
        ]);

        return response()->json(['token' => $token->plainTextToken]);
    }

    /**
     * Obtiene la información del usuario actualmente autenticado.
     *
     * @param  Request  $request  La solicitud HTTP.
     * @return array<string, mixed> Un array con los datos públicos del usuario.
     */
    public function user(Request $request): array
    {
        /** @var StaffUsers|null $user */
        $user = $request->user();

        if (! $user instanceof StaffUsers) {
            return [];
        }

        return $user->only([
            'id',
            'name',
            'email',
            'created_at',
            'updated_at',
            'email_verified_at',
        ]);
    }
}
