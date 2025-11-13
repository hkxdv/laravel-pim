<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\Ziggy;

/**
 * Proveedor de servicios para la configuración de rutas.
 *
 * Este proveedor es responsable de cargar los archivos de rutas de la aplicación,
 * configurar los patrones de ruta globales, definir los limitadores de velocidad (rate limiters)
 * y registrar cualquier configuración relacionada con el enrutamiento.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * La ruta a la que se redirige a los usuarios después de la autenticación.
     */
    public const string HOME = '/internal/dashboard';

    /**
     * Define los enlaces de modelos de ruta, filtros de patrones y otra configuración de rutas.
     *
     * Este método se llama durante el arranque de la aplicación y es el punto de entrada
     * para toda la configuración de enrutamiento.
     */
    public function boot(): void
    {
        // Configura patrones globales para parámetros de ruta comunes.
        // Esto ayuda a mantener la consistencia y la validación a nivel de enrutamiento.
        Route::pattern('id', '[0-9]+');       // Solo permite números enteros para {id}.
        Route::pattern('slug', '[a-z0-9-]+'); // Permite slugs alfanuméricos con guiones para {slug}.

        // Carga y configura los limitadores de velocidad para la aplicación.
        $this->configureRateLimiting();

        // Define los grupos de rutas para Ziggy
        // Esto permite que podamos filtrar qué rutas se envían al frontend
        $this->configureZiggyRouteGroups();

        // Carga los archivos de rutas de la aplicación.
        $this->routes(function (): void {
            // Rutas de API: usan el middleware 'api' y el prefijo '/api'.
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Rutas web: usan el middleware 'web' para sesiones, CSRF, etc.
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        // Registra la ruta especial para que Ziggy pueda obtener las rutas del frontend.
        $this->registerZiggyRoutes();
    }

    /**
     * Configura los limitadores de velocidad para la aplicación.
     *
     * Define diferentes políticas de limitación para proteger la aplicación contra
     * ataques de fuerza bruta o uso excesivo de la API.
     */
    private function configureRateLimiting(): void
    {
        // Límite general para la API: 60 solicitudes por minuto.
        // Se identifica por el ID del usuario autenticado o, si es un invitado, por su IP.
        RateLimiter::for(
            'api',
            fn (Request $request) => Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
        );

        // Límite para intentos de autenticación: 5 por minuto por IP.
        // Esto ayuda a prevenir ataques de fuerza bruta en el formulario de login.
        RateLimiter::for(
            'auth',
            fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        // Límite para operaciones con Sanctum (ej. emisión de tokens).
        RateLimiter::for(
            'sanctum',
            fn (Request $request) => Limit::perMinute(10)->by($request->ip())
        );
    }

    /**
     * Define los grupos de rutas para Ziggy
     * Esto permite que podamos filtrar qué rutas se envían al frontend
     */
    private function configureZiggyRouteGroups(): void
    {
        // Rutas públicas (accesibles para visitantes no autenticados)
        config(['ziggy.groups.public' => [
            'welcome',
            'register.redirect',
            'login',
            'login.store',
            'password.*',
            'sanctum.csrf-cookie',
        ]]);

        // Rutas para el panel interno de personal
        config(['ziggy.groups.staff' => [
            'internal.*',
            'logout',
            'verification.*',
            'password.confirm',
            'password.confirm.store',
            'sanctum.csrf-cookie',
            'storage.local',
        ]]);
    }

    /**
     * Registra la ruta para que Ziggy genere las rutas del lado del cliente.
     *
     * Esta ruta permite al frontend solicitar las rutas de Laravel que necesita,
     * filtradas por grupos para mayor seguridad y eficiencia.
     */
    private function registerZiggyRoutes(): void
    {
        Route::get('/api/routes', function (Request $request) {
            $groupsRaw = $request->input('groups');
            $groupsInput = is_string($groupsRaw) ? $groupsRaw : '';

            /** @var array<int, string> $requestedGroups */
            $requestedGroups = $groupsInput === ''
                ? []
                : array_filter(
                    array_map(
                        mb_trim(...),
                        explode(',', $groupsInput)
                    ),
                    static fn (string $g): bool => $g !== ''
                );

            // Asegura que el grupo 'public' (que contiene rutas como 'welcome') siempre esté disponible
            // para cualquier solicitud, evitando que el frontend se rompa si no lo solicita explícitamente.
            $groupsToFilter = array_unique(
                array_merge(
                    ['public'],
                    $requestedGroups
                )
            );

            return app(Ziggy::class)
                ->filter($groupsToFilter)
                ->toArray();
        })->middleware('web');
    }
}
