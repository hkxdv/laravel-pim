<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\ViewComposerInterface;
use App\Models\StaffUsers;
use App\Traits\PermissionVerifier;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Inertia\Response as InertiaResponse;

/**
 * Controlador para el dashboard principal del sistema.
 *
 * Gestiona la visualización de la página principal a la que acceden los usuarios
 * del personal tras iniciar sesión. Se encarga de coordinar los servicios
 * para obtener los módulos disponibles y componer los datos para la vista.
 */
final class InternalDashboardController extends Controller
{
    use PermissionVerifier;

    /**
     * Constructor del controlador del dashboard.
     */
    public function __construct(
        private readonly ModuleRegistryInterface $moduleRegistryService,
        private readonly NavigationBuilderInterface $navigationBuilderService,
        private readonly ViewComposerInterface $viewComposerService,
    ) {
        $this->middleware(['auth:staff']);
    }

    /**
     * Muestra el dashboard principal con los módulos disponibles para el usuario.
     *
     * Obtiene los módulos a los que el usuario tiene acceso, prepara los datos
     * necesarios para la vista y renderiza el componente de Inertia 'internal-dashboard'.
     */
    public function index(Request $request): InertiaResponse
    {
        try {
            /** @var StaffUsers|null $user */
            $user = $request->user('staff');

            // Verificar que tenemos un usuario autenticado
            if (! $user) {
                Log::warning(
                    'Acceso al dashboard sin usuario autenticado',
                    [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
                abort(403, 'Usuario no autenticado');
            }

            // Verificar si el usuario está activo
            if (! $this->isUserActive($user)) {
                Auth::guard('staff')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::warning(
                    'Usuario inactivo intentó acceder al dashboard',
                    [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip' => $request->ip(),
                    ]
                );

                abort(
                    403,
                    'Tu cuenta está inactiva. Contacta al administrador.'
                );
            }

            // Actualizar última actividad
            $this->updateLastActivity($user);

            // Obtener los módulos disponibles para el usuario según sus permisos.
            $availableModules = $this->moduleRegistryService
                ->getAvailableModulesForUser($user);

            // Asegurar que la colección de módulos sea un array indexado para el frontend.
            $indexedModules = array_values($availableModules);

            // Construir los ítems de navegación principales usando el servicio
            $mainNavItems = $this->navigationBuilderService->buildNavItems(
                $indexedModules,
                $this->can(...)
            );

            // Verificar si necesita cambiar contraseña
            $passwordChangeRequired = $this->isPasswordChangeRequired($user);

            // Preparar el contexto completo para la vista
            $viewData = $this->viewComposerService
                ->composeDashboardViewContext(
                    user: $user,
                    availableModules: $indexedModules,
                    permissionChecker: $this->can(...),
                    request: $request
                );

            // Agregar información adicional de seguridad
            $viewData = array_merge($viewData, [
                'passwordChangeRequired' => $passwordChangeRequired,
                'lastLogin' => $this->getLastLoginInfo($user),
                'sessionInfo' => $this->getSessionInfo($request),
                'mainNavItems' => $mainNavItems,
            ]);

            // Log de acceso exitoso
            Log::info(
                'Acceso exitoso al dashboard interno',
                [
                    'user_id' => $user->getAuthIdentifier(),
                    'email' => $user->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Renderizar la vista del dashboard
            return inertia('internal-dashboard', $viewData);
        } catch (Exception $exception) {
            Log::error(
                'Error en dashboard interno',
                [
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                    'ip' => $request->ip(),
                ]
            );

            abort(500, 'Error interno. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Cerrar sesión de forma segura.
     */
    public function logout(Request $request): RedirectResponse
    {
        try {
            $user = Auth::guard('staff')->user();

            if ($user) {
                Log::info(
                    'Logout exitoso del dashboard interno',
                    [
                        'user_id' => $user->getAuthIdentifier(),
                        'email' => $user->email,
                        'ip' => $request->ip(),
                    ]
                );
            }

            Auth::guard('staff')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return to_route('login')
                ->with('status', 'Sesión cerrada exitosamente.');
        } catch (Exception $exception) {
            Log::error(
                'Error durante logout',
                [
                    'error' => $exception->getMessage(),
                    'ip' => $request->ip(),
                ]
            );

            return to_route('login');
        }
    }

    /**
     * Verificar si el usuario está activo.
     */
    private function isUserActive(StaffUsers $user): bool
    {
        if (isset($user->active)) {
            return (bool) $user->active;
        }

        if (isset($user->status)) {
            return $user->status === 'active';
        }

        return true;
    }

    /**
     * Actualizar la última actividad del usuario.
     */
    private function updateLastActivity(StaffUsers $user): void
    {
        try {
            // Solo intentar persistir si la columna existe en la tabla
            if (Schema::hasColumn('staff_users', 'last_activity')) {
                $now = \Illuminate\Support\Facades\Date::now();
                /** @var \Illuminate\Support\Carbon|null $lastActivity */
                $lastActivity = $user->getAttribute('last_activity');

                // Evitar escrituras frecuentes: solo persistir si pasaron >=5 minutos
                if ($lastActivity instanceof Carbon) {
                    $minutes = $lastActivity->diffInMinutes($now);
                    if ($minutes < 5) {
                        return; // Skip write
                    }
                }

                // Evitar restricciones de asignación masiva usando forceFill
                $user->forceFill([
                    'last_activity' => $now,
                ])->save();
            }
        } catch (Exception $exception) {
            Log::warning(
                'No se pudo actualizar la última actividad',
                [
                    'user_id' => $user->getAuthIdentifier(),
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Verificar si se requiere cambio de contraseña.
     */
    private function isPasswordChangeRequired(StaffUsers $user): bool
    {
        if (! isset($user->password_changed_at)) {
            return false;
        }

        $maxAge = config(
            'security.authentication.passwords.staff.max_age_days',
            90
        );

        /** @var \Illuminate\Support\Carbon|string|int|float|null $passwordChangedAt */
        $passwordChangedAt = $user->password_changed_at;
        $passwordAge = \Illuminate\Support\Facades\Date::parse($passwordChangedAt)
            ->diffInDays(\Illuminate\Support\Facades\Date::now());

        return $passwordAge >= $maxAge;
    }

    /**
     * Obtener información del último login.
     *
     * @return array{datetime: \Carbon\CarbonImmutable, ip: string, user_agent: string}|null
     */
    private function getLastLoginInfo(StaffUsers $user): ?array
    {
        if (! isset($user->last_login_at)) {
            return null;
        }

        /** @var \Illuminate\Support\Carbon|string|int|float|null $lastLoginAt */
        $lastLoginAt = $user->last_login_at;

        return [
            'datetime' => \Illuminate\Support\Facades\Date::parse($lastLoginAt),
            'ip' => is_string($user->getAttribute('last_login_ip'))
                ? $user->getAttribute('last_login_ip')
                : 'Desconocida',
            'user_agent' => is_string($user->getAttribute('last_login_user_agent'))
                ? $user->getAttribute('last_login_user_agent')
                : 'Desconocido',
        ];
    }

    /**
     * Obtener información de la sesión actual.
     *
     * @return array{ip: string|null, user_agent: string|null, session_id: string, started_at: \Carbon\CarbonImmutable}
     */
    private function getSessionInfo(Request $request): array
    {
        $rawStartedAt = Session::get('_token_created_at', time());
        $timestamp = is_int($rawStartedAt) || is_float($rawStartedAt)
            ? (int) $rawStartedAt
            : (is_string($rawStartedAt) ? (int) $rawStartedAt : time());

        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => Session::getId(),
            'started_at' => \Illuminate\Support\Facades\Date::createFromTimestamp($timestamp),
        ];
    }
}
