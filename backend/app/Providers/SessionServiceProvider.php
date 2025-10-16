<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\QueryException;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use UnitEnum;

/**
 * Proveedor de servicios para la gestión de sesiones.
 *
 * Este proveedor personaliza el comportamiento del manejador de sesiones de base de datos
 * de Laravel para que funcione con la columna `staff_user_id` en lugar de la `user_id` estándar.
 */
final class SessionServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de sesión personalizados.
     *
     * Aquí se extiende el manejador de sesiones de base de datos de Laravel para reemplazarlo
     * con nuestra implementación personalizada (CustomDatabaseSessionHandler).
     */
    public function register(): void
    {
        $this->app->resolving(
            'session',
            function (
                \Illuminate\Session\SessionManager $sessionManager
            ): void {
                $sessionManager->extend(
                    'database',
                    function (
                        \Illuminate\Contracts\Container\Container $app
                    ): CustomDatabaseSessionHandler {
                        /** @var \Illuminate\Contracts\Config\Repository $configRepo */
                        $configRepo = $app->make(\Illuminate\Contracts\Config\Repository::class);

                        /** @var array<string, mixed> $config */
                        $config = (array) $configRepo->get('session', []);

                        $tableValue = $config['table'] ?? $configRepo->get('session.table');
                        $table = is_string($tableValue) ? $tableValue : 'sessions';

                        $lifetimeValue = $config['lifetime'] ?? $configRepo->get('session.lifetime');
                        $lifetime = is_int($lifetimeValue)
                            ? $lifetimeValue
                            : (is_string($lifetimeValue)
                                ? (int) $lifetimeValue
                                : 120
                            );

                        $connectionValue = $config['connection'] ?? $configRepo->get('session.connection');

                        /** @var string|UnitEnum|null $connection */
                        $connection = $connectionValue instanceof UnitEnum || is_string($connectionValue)
                            ? $connectionValue
                            : null;

                        /** @var \Illuminate\Database\DatabaseManager $db */
                        $db = $app->make(\Illuminate\Database\DatabaseManager::class);

                        /** @var \Illuminate\Database\ConnectionInterface $connectionInstance */
                        $connectionInstance = $db->connection($connection);

                        return new CustomDatabaseSessionHandler(
                            $connectionInstance,
                            $table,
                            $lifetime,
                            $app
                        );
                    }
                );
            }
        );
    }

    /**
     * Arranca los servicios de sesión.
     *
     * Este método define un listener para el evento de login que se encarga de una
     * casuística específica para entornos de prueba que usan SQLite.
     */
    public function boot(): void
    {
        // El contenedor de la aplicación está disponible en los ServiceProviders
        // y no es nulo en tiempo de ejecución.

        // Escucha el evento de login para sincronizar el 'user_id' en entornos SQLite.
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event): void {
                // Esta lógica es una solución temporal para cuando se usa SQLite en pruebas.
                // Algunas partes de Laravel o paquetes de terceros pueden depender de la columna 'user_id',
                // y este listener asegura que se rellene después del login, aunque nuestro manejador
                // personalizado se centre en 'staff_user_id'.

                /** @var \Illuminate\Contracts\Config\Repository $configRepo */
                $configRepo = $this->app->make(\Illuminate\Contracts\Config\Repository::class);

                $sessionConnectionValue = $configRepo->get(
                    'session.connection'
                );
                /** @var string|UnitEnum|null $sessionConnection */
                $sessionConnection = $sessionConnectionValue
                    instanceof UnitEnum || is_string($sessionConnectionValue)
                    ? $sessionConnectionValue
                    : null;

                $driver = DB::connection($sessionConnection)->getDriverName();

                /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
                $user = $event->user;

                if ($driver === 'sqlite') {
                    $tableValue = $configRepo->get('session.table');
                    $table = is_string($tableValue) ? $tableValue : 'sessions';
                    DB::table($table)
                        ->where(
                            'id',
                            $this->app->make(\Illuminate\Session\SessionManager::class)->getId()
                        )
                        ->update(
                            ['user_id' => $user->getAuthIdentifier()]
                        );
                }
            }
        );
    }
}

/**
 * Manejador de sesiones de base de datos personalizado.
 *
 * Sobrescribe el manejador por defecto de Laravel para utilizar la columna `staff_user_id`
 * para usuarios autenticados con el guard de staff. Esto permite que el sistema de sesiones
 * funcione correctamente con el modelo de usuarios del personal.
 */
final class CustomDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Añade la información del usuario al payload de la sesión.
     *
     * @param  array<string, mixed>  $payload
     * @return $this
     */
    protected function addUserInformation(&$payload)
    {
        if ($this->container && $this->container->bound('auth')) {
            $userId = $this->userId();
            if ($userId) {
                // Determinar el tipo de usuario basado en el guard activo
                $currentGuard = $this->getCurrentGuard();

                if ($currentGuard === 'staff') {
                    $payload['staff_user_id'] = $userId;
                }

                // Mantener user_id para compatibilidad
                $payload['user_id'] = $userId;
            }
        }

        return $this;
    }

    /**
     * Realiza la actualización de una sesión existente en la base de datos.
     *
     * @param  string  $sessionId
     * @param  array<string, mixed>  $payload
     */
    protected function performUpdate($sessionId, $payload)
    {
        // Partir del payload por defecto generado por el manejador base
        $updateData = $payload;
        $updateData['ip_address'] = request()->ip();
        $updateData['user_agent'] = request()->userAgent();

        if ($userId = $this->userId()) {
            $currentGuard = $this->getCurrentGuard();

            if ($currentGuard === 'staff') {
                $updateData['staff_user_id'] = $userId;
            }

            // Mantener user_id para compatibilidad
            $updateData['user_id'] = $userId;
        }

        return $this->getQuery()->where('id', $sessionId)->update($updateData);
    }

    /**
     * Realiza la inserción de una nueva sesión en la base de datos.
     *
     * @param  string  $sessionId
     * @param  array<string, mixed>  $payload
     */
    protected function performInsert($sessionId, $payload)
    {
        // Partir del payload por defecto generado por el manejador base
        $insertData = $payload;
        $insertData['id'] = $sessionId;
        $insertData['ip_address'] = request()->ip();
        $insertData['user_agent'] = request()->userAgent();

        if ($userId = $this->userId()) {
            $currentGuard = $this->getCurrentGuard();

            if ($currentGuard === 'staff') {
                $insertData['staff_user_id'] = $userId;
            }

            // Mantener user_id para compatibilidad
            $insertData['user_id'] = $userId;
        }

        try {
            return $this->getQuery()->insert($insertData);
        } catch (QueryException) {
            $this->performUpdate($sessionId, $insertData);

            return null;
        }
    }

    /**
     * Obtiene el guard actualmente autenticado.
     */
    private function getCurrentGuard(): ?string
    {
        if (! $this->container) {
            return null;
        }

        $auth = $this->container->make(
            \Illuminate\Contracts\Auth\Factory::class
        );

        // Verificar guard staff
        if ($auth->guard('staff')->check()) {
            return 'staff';
        }

        return null;
    }
}
