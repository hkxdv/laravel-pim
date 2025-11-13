<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Proveedor de servicios para Laravel Telescope.
 *
 * Este proveedor configura el comportamiento de Telescope, incluyendo el registro de entradas,
 * la ofuscación de datos sensibles y el control de acceso al panel de Telescope.
 */
final class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Registra los servicios de la aplicación para Telescope.
     *
     * Este método se encarga de registrar los filtros que determinan qué información
     * se captura y se muestra en el panel de Telescope.
     */
    public function register(): void
    {
        // Activar el tema oscuro de Telescope (opcional, descomentar si se desea).
        // Telescope::night();

        // Oculta detalles sensibles de las peticiones para proteger la privacidad.
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        // Define qué entradas deben ser registradas por Telescope.
        // En entornos locales, se registra todo. En otros entornos, solo se registran
        // excepciones, peticiones fallidas, trabajos fallidos, tareas programadas y etiquetas monitoreadas.
        Telescope::filter(
            function (IncomingEntry $entry) use ($isLocal): bool {
                if ($isLocal) {
                    return true;
                }

                if ($entry->isReportableException()) {
                    return true;
                }

                if ($entry->isFailedRequest()) {
                    return true;
                }

                if ($entry->isFailedJob()) {
                    return true;
                }

                if ($entry->isScheduledTask()) {
                    return true;
                }

                return $entry->hasMonitoredTag();
            }
        );
    }

    /**
     * Registra la puerta de acceso (gate) para Telescope.
     *
     * Esta puerta determina quién puede acceder a Telescope en entornos no locales.
     * Es una medida de seguridad crucial para proteger la información de depuración.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user): bool {
            // Asegurar que $user es un objeto válido antes de invocar métodos
            if (! is_object($user)) {
                return false;
            }

            // Por defecto, solo los usuarios con roles de 'ADMIN' o 'DEV' pueden acceder a Telescope.
            // Esto previene la exposición de datos sensibles a usuarios no autorizados.
            if (method_exists($user, 'hasRole')) {
                if ($user->hasRole('ADMIN')) {
                    return true;
                }

                return (bool) $user->hasRole('DEV');
            }

            return false;
        });
    }

    /**
     * Evita que detalles sensibles de las peticiones sean registrados por Telescope.
     *
     * En entornos no locales, se ofuscan parámetros y cabeceras que puedan contener
     * información sensible como tokens o cookies.
     */
    private function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        // Oculta el token CSRF de los parámetros de la petición.
        Telescope::hideRequestParameters(['_token']);

        // Oculta cabeceras que puedan contener información de sesión o tokens.
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }
}
