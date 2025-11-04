<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Services\LoginAttemptService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Maneja la lógica de validación y autenticación para el inicio de sesión.
 * Este FormRequest ahora está enfocado exclusivamente en el inicio de sesión del personal (staff).
 */
final class LoginRequest extends FormRequest
{
    /**
     * El tipo de inicio de sesión.
     */
    private string $loginType;

    /**
     * El guarda de autenticación a utilizar.
     */
    private string $guard;

    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     * También establece el tipo de login y el guard a utilizar.
     */
    public function authorize(): bool
    {
        $this->loginType = 'staff';
        $this->guard = 'staff';

        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para el validador.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.regex' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /**
     * Intenta autenticar las credenciales de la solicitud.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginAttemptService = app(LoginAttemptService::class);
        $credentials = $this->getCredentials();
        $rawIdentifier = $this->input('email');
        $identifier = is_string($rawIdentifier)
            ? $rawIdentifier
            : $credentials['email'];
        $ipRaw = $this->ip();
        $ip = is_string($ipRaw) ? $ipRaw : '';

        // Verificar si el usuario existe antes de intentar autenticar
        $user = $this->findUser($credentials);

        if (! $user instanceof Authenticatable) {
            $this->handleFailedLogin(
                $loginAttemptService,
                $identifier,
                $ip,
                'user_not_found'
            );
        }

        // Verificar si la cuenta está activa
        if ($user && ! $this->isUserActive($user)) {
            $this->handleFailedLogin(
                $loginAttemptService,
                $identifier,
                $ip,
                'account_inactive'
            );
        }

        if (
            ! Auth::guard(
                $this->guard
            )->attempt($credentials, $this->boolean('remember'))
        ) {
            $this->handleFailedLogin(
                $loginAttemptService,
                $identifier,
                $ip,
                'invalid_credentials'
            );
        }

        // Autenticación exitosa, limpiar el contador de intentos fallidos
        $loginAttemptService->clearAttempts($identifier, $ip);

        // Log de login exitoso
        Log::info('Login exitoso', [
            'user_id' => Auth::guard($this->guard)->id(),
            'guard' => $this->guard,
            'login_type' => $this->loginType,
            'ip' => $ip,
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * Obtiene las credenciales para el intento de autenticación.
     *
     * @return array{email: string, password: string}
     */
    public function getCredentials(): array
    {
        $rawEmail = $this->input('email');
        $email = is_string($rawEmail) ? $rawEmail : '';
        $rawPassword = $this->input('password');
        $password = is_string($rawPassword) ? $rawPassword : '';

        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * Asegura que la solicitud de inicio de sesión no esté limitada por frecuencia.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $loginAttemptService = app(LoginAttemptService::class);
        $rawIdentifier = $this->input('email');
        $identifier = is_string($rawIdentifier) ? $rawIdentifier : '';
        $ipRaw = $this->ip();
        $ip = is_string($ipRaw) ? $ipRaw : '';

        // Primero comprueba si la IP está bloqueada (bloqueo de nivel superior)
        if ($loginAttemptService->isIpBlocked($ip)) {
            event(new Lockout($this));

            Log::warning(
                'Acceso bloqueado por IP en lista negra.',
                [
                    'identifier' => $identifier,
                    'ip' => $ip,
                ]
            );

            throw ValidationException::withMessages([
                'email' => __('Acceso bloqueado temporalmente por motivos de seguridad.'),
            ]);
        }

        // Luego comprueba los intentos individuales
        if (! $loginAttemptService->hasTooManyAttempts($identifier, $ip)) {
            return;
        }

        event(new Lockout($this));

        $minutes = $loginAttemptService->getRemainingMinutes($identifier, $ip);
        $seconds = $minutes * 60;

        Log::warning('Bloqueo de inicio de sesión por exceso de intentos.', [
            'identifier' => $identifier,
            'ip' => $ip,
            'minutes_remaining' => $minutes,
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => $minutes,
            ]),
        ]);
    }

    /**
     * Obtiene la URL a la que se debe redirigir después de un inicio de sesión exitoso.
     */
    protected function getRedirectUrl(): string
    {
        $intended = session()->pull('url.intended');

        return is_string($intended)
            ? $intended
            : route('internal.dashboard');
    }

    /**
     * Buscar usuario por credenciales en el guard específico.
     *
     * @param  array{email: string, password: string}  $credentials
     */
    private function findUser(array $credentials): ?Authenticatable
    {
        $provider = Config::get(sprintf('auth.guards.%s.provider', $this->guard));
        if (! is_string($provider) || $provider === '') {
            return null;
        }

        $model = Config::get(sprintf('auth.providers.%s.model', $provider));
        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return null;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model&Authenticatable> $modelClass */
        $modelClass = $model;
        /** @var \Illuminate\Database\Eloquent\Model|null $found */
        $found = $modelClass::where('email', $credentials['email'])->first();

        return $found instanceof Authenticatable ? $found : null;
    }

    /**
     * Verificar si el usuario está activo.
     */
    private function isUserActive(Authenticatable $user): bool
    {
        // Verificar si el modelo tiene campo 'active' o 'status'
        if (property_exists($user, 'active') && $user->active !== null) {
            return (bool) $user->active;
        }

        if (property_exists($user, 'status') && $user->status !== null) {
            return $user->status === 'active';
        }

        // Por defecto, asumir que está activo si no hay campo de estado
        return true;
    }

    /**
     * Manejar fallos de login.
     */
    private function handleFailedLogin(
        LoginAttemptService $loginAttemptService,
        string $identifier,
        string $ip,
        string $reason
    ): void {
        // Registrar el intento fallido usando el servicio
        $loginAttemptService->incrementAttempts($identifier, $ip);
        $this->logFailedAttempt($reason);

        $message = match ($reason) {
            'user_not_found' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            'account_inactive' => 'Tu cuenta está inactiva. Contacta al administrador.',
            'invalid_credentials' => 'Las credenciales proporcionadas no son correctas.',
            default => __('auth.failed')
        };

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }

    /**
     * Registra un intento de inicio de sesión fallido para análisis de seguridad.
     */
    private function logFailedAttempt(string $reason): void
    {
        Log::warning('Intento de inicio de sesión fallido', [
            'reason' => $reason,
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'email' => $this->input('email'),
        ]);
    }
}
