<?php

declare(strict_types=1);

/**
 * Archivo de inicialización de la aplicación Laravel.
 * Configura la aplicación, incluyendo el enrutamiento, middleware, y manejo de excepciones.
 */


// Verificar si se debe mostrar errores detallados de Laravel en lugar de los personalizados de Inertia
// Importante: Evitar usar Facades antes de que la aplicación esté creada; capturamos la Request directamente.
$showLaravelErrors = \Illuminate\Http\Request::capture()->query('show_laravel_errors') !== null
    || (bool) (\Illuminate\Support\Env::get('SHOW_LARAVEL_ERRORS', false));

/** @var callable $middlewareConfigurator */
$middlewareConfigurator = require __DIR__ . '/modules/middleware.php';

$providers = (array) (require __DIR__ . '/providers.php');

/** @var callable $exceptionsConfiguratorFactory */
$exceptionsConfiguratorFactory = require __DIR__ . '/modules/exceptions.php';

/** @var callable $exceptionsConfigurator */
$exceptionsConfigurator = $exceptionsConfiguratorFactory($showLaravelErrors);

$application = \Illuminate\Foundation\Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware($middlewareConfigurator)
    ->withProviders($providers)
    ->withExceptions($exceptionsConfigurator)
    ->create();

/** @var callable $pathsBootstrap */
$pathsBootstrap = require __DIR__ . '/modules/paths.php';
$pathsBootstrap($application);

/** @var callable $bindingsBootstrap */
$bindingsBootstrap = require __DIR__ . '/modules/bindings.php';
$bindingsBootstrap($application);

/** @var callable $envBootstrap */
$envBootstrap = require __DIR__ . '/modules/env.php';
$envBootstrap($application);

return $application;
