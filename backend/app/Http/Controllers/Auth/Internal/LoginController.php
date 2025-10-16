<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Controlador para gestionar la autenticación del personal interno.
 *
 * Se encarga de mostrar el formulario de login, procesar el inicio de sesión
 * y gestionar el cierre de sesión para los usuarios del panel de administración.
 */
final class LoginController extends Controller
{
    /**
     * Crea una nueva instancia del controlador.
     *
     * Inyecta el servicio de auditoría de seguridad para ser utilizado
     * en los métodos de almacenamiento y destrucción de la sesión.
     *
     * @param  SecurityAuditService  $securityService  El servicio para manejar la auditoría de seguridad.
     */
    public function __construct(
        private readonly SecurityAuditService $securityService
    ) {}

    /**
     * Muestra la vista de inicio de sesión para el personal.
     *
     * Renderiza un componente de Inertia genérico ('auth/login') y le pasa todas
     * las propiedades necesarias para configurar el formulario de login del personal.
     */
    public function create(): Response
    {
        $errors = session('errors');
        $errorMessages = (object) [];
        if ($errors instanceof \Illuminate\Support\ViewErrorBag) {
            $errorMessages = (object) $errors->getBag('default')->getMessages();
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'errors' => $errorMessages,
            'postUrl' => route('login'),
            // Textos genéricos y neutrales
            'pageTitle' => 'Iniciar sesión',
            'formTitle' => 'Inicio de sesión',
            'formDescription' => 'Accede con tus credenciales',
            'emailFieldLabel' => 'Correo electrónico',
            'emailFieldType' => 'email',
            'emailFieldAutoComplete' => 'email',
            'submitButtonText' => 'Iniciar sesión',
            'forgotPasswordUrl' => Route::has('password.request') ? route('password.request') : null,
            // No enviar blockquote para mantener la columna derecha neutral
            // 'blockquoteText' => null,
            // 'blockquoteFooter' => null,
        ]);
    }

    /**
     * Maneja una solicitud de autenticación entrante.
     *
     * @param  LoginRequest  $request  La solicitud de login validada.
     */
    public function store(LoginRequest $request): SymfonyResponse
    {
        // 1. Autenticar al usuario.
        // El LoginRequest se encarga de la validación y de intentar la autenticación.
        // Si falla, lanzará una ValidationException automáticamente.
        $request->authenticate();

        // 2. Preparar la sesión autenticada.
        // El servicio de seguridad regenera el token de sesión para prevenir ataques de session fixation.
        $this->securityService->prepareAuthenticatedSession($request);

        /** @var \App\Models\StaffUsers|null $user */
        $user = Auth::guard('staff')->user();

        // 3. Gestionar notificaciones de seguridad.
        // Si el usuario se autenticó correctamente, se verifica si el login es sospechoso y se notifica si es necesario.
        if ($user) {
            $this->securityService->handleSuspiciousLoginNotification($user, $request);
        }

        // 4. Redirigir al usuario.
        // Se redirige al dashboard o a la URL a la que intentaba acceder originalmente.
        // NOTA: Usamos Inertia::location para forzar una navegación de página completa y evitar
        // problemas de cookies SameSite al realizar redirecciones vía XHR entre orígenes distintos
        // (por ejemplo, frontend en :5173 y backend en :8080) durante el desarrollo.
        $intended = redirect()->intended(route('internal.dashboard'))->getTargetUrl();

        return Inertia::location($intended);
    }

    /**
     * Destruye una sesión autenticada (cierre de sesión).
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Registrar logout para auditoría de seguridad si está en modo debug
        if (config('app.debug')) {
            Log::info('Logout de usuario staff', [
                'user_id' => Auth::guard('staff')->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Cerrar sesión directamente usando el guard de staff
        Auth::guard('staff')->logout();

        // Invalidar la sesión manualmente
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Olvidar todas las variables de sesión relacionadas con autenticación
            $request->session()->forget(['auth', 'auth.password_confirmed_at']);
        }

        // Limpiar cookies si existen
        $cookieName = config('session.cookie');
        if (is_string($cookieName)) {
            Cookie::forget($cookieName);
        }

        // Redirige el usuario a la página de inicio de sesión con mensaje de confirmación
        return to_route('login')
            ->with('status', 'Tu sesión ha sido cerrada correctamente');
    }
}
