<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Filtrado de Rutas para Ziggy
    |--------------------------------------------------------------------------
    |
    | Esta configuración define los patrones de rutas disponibles para cada tipo de usuario.
    | Puedes usar comodines (*) para representar cualquier número de caracteres en los nombres de ruta.
    |
    */

    'filters' => [
        /*
        |--------------------------------------------------------------------------
        | Rutas Públicas
        |--------------------------------------------------------------------------
        |
        | Estas rutas están disponibles para todos los usuarios, incluso si no están autenticados.
        | Incluye rutas de autenticación, registro, bienvenida, etc.
        |
        */
        'public' => [
            // Rutas básicas realmente necesarias para visitantes
            'welcome',
            'requirements.index',
            'ziggy-debug',

            // Autenticación interna - solo rutas de login específicas
            'login',
            'login.store',
            'password.request',
            'password.email',
            'password.reset',

            // Sistema - solo lo esencial
            'sanctum.csrf-cookie',
        ],

        /*
        |--------------------------------------------------------------------------
        | Rutas para Staff
        |--------------------------------------------------------------------------
        |
        | Estas rutas adicionales están disponibles para usuarios de staff autenticados.
        |
        */
        'staff' => [
            'internal.*',
            'api.*',
        ],
    ],

];
