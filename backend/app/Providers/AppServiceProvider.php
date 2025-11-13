<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\ApiResponseFormatterInterface;
use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\ViewComposerInterface;
use App\Models\StaffUsers;
use App\Services\ApiResponseService;
use App\Services\JsonbQueryService;
use App\Services\ModuleRegistryService;
use App\Services\NavigationBuilderService;
use App\Services\RouteFilterService;
use App\Services\ViewComposerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Scout;
use Typesense\Client;

/**
 * Proveedor de servicios principal de la aplicación.
 *
 * Este proveedor es el lugar central para registrar enlaces en el contenedor de servicios,
 * configurar observadores de modelos, definir gates de autorización y realizar otras
 * tareas de arranque esenciales para la aplicación.
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de la aplicación en el contenedor de dependencias.
     *
     * Aquí se configuran las implementaciones para las interfaces clave del sistema,
     * se establece la ruta de la base de datos para comandos de consola y se registran
     * proveedores de servicios específicos para entornos de desarrollo, como Telescope.
     */
    public function register(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        // Establece una ruta personalizada para la base de datos.
        // Esto es útil para que los comandos Artisan como 'migrate' encuentren la
        // base de datos en la estructura de directorios del proyecto.
        $app->useDatabasePath(base_path('../database'));

        // Registra Telescope solo en entornos de no producción y si el paquete está instalado.
        if (
            ! $this->app->environment('production')
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)
        ) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);

            if (class_exists(TelescopeServiceProvider::class)) {
                // Registrar el proveedor local si existe.
                $this->app->register(TelescopeServiceProvider::class);
            }
        }

        // Registrar las interfaces del sistema con sus implementaciones concretas.
        // Esto permite la inyección de dependencias y desacopla los componentes.
        $this->app->singleton(
            ApiResponseFormatterInterface::class,
            ApiResponseService::class
        );
        $this->app->singleton(JsonbQueryService::class);
        $this->app->singleton(ModuleRegistryService::class);
        $this->app->singleton(NavigationBuilderService::class);
        $this->app->singleton(RouteFilterService::class);

        // Bindings adicionales para interfaces
        $this->app->bind(
            ModuleRegistryInterface::class,
            ModuleRegistryService::class
        );
        $this->app->bind(
            NavigationBuilderInterface::class,
            NavigationBuilderService::class
        );
        $this->app->bind(
            ViewComposerInterface::class,
            ViewComposerService::class
        );
    }

    /**
     * Arranca los servicios de la aplicación después de que se hayan registrado.
     *
     * Este método es ideal para registrar listeners de eventos, políticas de autorización
     * o cualquier otra funcionalidad que dependa de que otros servicios ya estén registrados.
     */
    public function boot(): void
    {
        // Define una regla global 'before' para la autorización.
        // Esta función se ejecuta antes de cualquier otra verificación de Gate o Política.
        // Otorga acceso total a los super-administradores para simplificar la gestión de permisos.
        Gate::before(function (
            \Illuminate\Contracts\Auth\Authenticatable $user,
            string $ability
        ): ?bool {
            // Si es nuestro modelo StaffUsers y tiene rol ADMIN o DEV, otorga acceso total
            if (
                $user instanceof StaffUsers
                && ($user->hasRole('ADMIN') || $user->hasRole('DEV'))
            ) {
                return true;
            }

            // Si no es un super-admin, no se interfiere (retornando null) y se permite
            // que las políticas y gates específicos para la habilidad decidan.
            return null;
        });

        // Registro del driver 'typesense' para Scout solo si está activo.
        $driverRaw = config('scout.driver');
        $driver = is_string($driverRaw) ? $driverRaw : '';
        if ($driver === 'typesense' && class_exists(Client::class)) {
            /** @var EngineManager $manager */
            $manager = $this->app->make(EngineManager::class);

            $manager->extend('typesense', function ($app): \App\Scout\TypesenseEngine {
                /** @var array<string, mixed> $clientSettings */
                $clientSettings = (array) config('scout.typesense.client-settings', []);
                $client = new Client($clientSettings);
                $prefixRaw = config('scout.prefix', '');
                $prefix = is_string($prefixRaw) ? $prefixRaw : '';
                /** @var array<string, mixed> $config */
                $config = (array) config('scout.typesense', []);

                return new \App\Scout\TypesenseEngine($client, $config, $prefix);
            });
        }
    }
}
