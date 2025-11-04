<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

final class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        Middleware\TrustProxies::class,
        Middleware\ForceHttps::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        Middleware\SecurityHeaders::class, // Agregado para mejorar seguridad
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // Prevenir almacenamiento en caché de páginas con información sensible
            \Illuminate\Http\Middleware\SetCacheHeaders::class,
            // Controlar logging para reducir verbosidad
            Middleware\LoggingMiddleware::class,
            Middleware\RequestHeadersAuditMiddleware::class,
        ],

        'api' => [
            // EnsureFrontendRequestsAreStateful detecta automáticamente si es una petición desde SPA
            // y aplica los middlewares adecuados solamente en ese caso
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // Estos middlewares se aplican a todas las solicitudes API
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Forzar HTTPS en producción (middleware recomendado para implementar)
            // \App\Http\Middleware\ForceHttps::class,

        ],

        'auth.staff' => [
            'auth:staff',
            'session.integrity:staff',
        ],

    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'permission' => Middleware\CheckPermission::class,
        'protect.assets' => Middleware\ProtectStaticAssets::class,
        'security-headers' => Middleware\SecurityHeaders::class,
        'session.integrity' => Middleware\ValidateSessionIntegrity::class,
        // 'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class, // Comentado - middleware no implementado

        /*
        // Middlewares de seguridad recomendados para implementar:
        'no-cache' => \App\Http\Middleware\PreventPageCaching::class,
        '2fa' => \App\Http\Middleware\TwoFactorAuthentication::class,
        'active-user' => \App\Http\Middleware\EnsureUserIsActive::class,
        */
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var string[]
     */
    protected $middlewarePriority = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        Middleware\ForceHttps::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        Middleware\ValidateSessionIntegrity::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        // \App\Http\Middleware\RateLimitMiddleware::class, // Comentado - middleware no implementado
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
