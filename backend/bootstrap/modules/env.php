<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;

return function (Application $application): void {
    // Raíz del monorepo (un nivel arriba de backend)
    $projectRootRaw = dirname($application->basePath());
    $projectRoot = realpath($projectRootRaw) ?: $projectRootRaw;

    // Detectar CLI / PHPUnit
    $isCli = PHP_SAPI === 'cli';
    $argvString = isset($_SERVER['argv'])
        ? implode(' ', (array) $_SERVER['argv']) : '';
    $isPhpUnit = $isCli && ($argvString !== '') && str_contains(
        $argvString,
        'phpunit'
    );

    // Detectar contenedor
    $runningInContainerEnv = Env::get(
        'APP_RUNNING_IN_CONTAINER',
        $_SERVER['APP_RUNNING_IN_CONTAINER'] ?? null
    );
    $runningInContainer = filter_var($runningInContainerEnv, FILTER_VALIDATE_BOOL) ?: (is_file('/.dockerenv'));

    // Entorno actual (si ya viene del sistema)
    $appEnv = Env::get('APP_ENV', $_SERVER['APP_ENV'] ?? null);

    // Configuración de bootstrap/env sin depender del contenedor
    $envConfig = [];
    try {
        $configFile = $application->basePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($configFile)) {
            $bootstrapConfig = require $configFile;
            if (
                is_array($bootstrapConfig)
                && isset($bootstrapConfig['env'])
                && is_array($bootstrapConfig['env'])
            ) {
                $envConfig = $bootstrapConfig['env'];
            }
        }
    } catch (\Throwable) {
        // Continuar con valores por defecto si falla la carga
        $envConfig = [];
    }

    $preferred = (string) ($envConfig['preferred'] ?? '.env.local');
    $testing = (string) ($envConfig['testing'] ?? '.env.testing');
    $docker = (string) ($envConfig['docker'] ?? '.env.docker');
    $production = (string) ($envConfig['production'] ?? '.env.production.local');
    $fallbackPg = (string) ($envConfig['fallback_pg'] ?? '.env.pg.local');
    $usersEnvFile = (string) ($envConfig['users_file'] ?? '.env.users');
    $required = (array) ($envConfig['required'] ?? ['APP_ENV', 'APP_KEY']);

    // Selección del archivo .env
    $envFile = $preferred;
    if ($isPhpUnit || $appEnv === 'testing') {
        $envFile = $testing;
    } elseif ($runningInContainer) {
        $envFile = $docker;
    } elseif ($appEnv === 'production') {
        $envFile = $production;
    } elseif (
        ! file_exists($projectRoot . DIRECTORY_SEPARATOR . $envFile)
        && file_exists($projectRoot . DIRECTORY_SEPARATOR . $fallbackPg)
    ) {
        // Fallback a configuración PostgreSQL local si .env.local no existe
        $envFile = $fallbackPg;
    }

    $envPath = $projectRoot . DIRECTORY_SEPARATOR . $envFile;
    if (is_file($envPath) && is_readable($envPath)) {
        // Cargar env desde raíz y alinear el bootstrapper
        Dotenv::createMutable($projectRoot, $envFile)->safeLoad();
        $application->loadEnvironmentFrom(
            '..' . DIRECTORY_SEPARATOR . $envFile
        );
    } elseif (! $isPhpUnit && $appEnv !== 'testing') {
        // Sin archivo .env legible; continuar con variables de sistema
        // En entorno de pruebas, no generar ruido en la salida
        error_log(sprintf('[bootstrap] Archivo de entorno no legible/no encontrado: %s', $envPath));
    }

    // Validaciones mínimas de variables requeridas
    foreach ($required as $key) {
        $val = Env::get($key, $_SERVER[$key] ?? null);
        if ($val === null || $val === '') {
            if ($key === 'APP_KEY') {
                // Generar APP_KEY si falta (útil en testing/CI)
                $random = base64_encode(random_bytes(32));
                $generated = 'base64:' . $random;
                putenv('APP_KEY=' . $generated);
                $_ENV['APP_KEY'] = $generated;
                $_SERVER['APP_KEY'] = $generated;
            } else {
                error_log(sprintf('[bootstrap] Variable requerida ausente: %s', $key));
            }
        }
    }

    // Cargar variables adicionales desde .env.users en la raíz del monorepo
    try {
        if (file_exists($projectRoot . DIRECTORY_SEPARATOR . $usersEnvFile)) {
            Dotenv::createMutable($projectRoot, $usersEnvFile)->safeLoad();
        }
    } catch (\Throwable) {
        // Ignorar cualquier error al cargar .env.users
    }
};
