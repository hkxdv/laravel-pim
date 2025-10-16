<?php

declare(strict_types=1);

return [
    /**
     * Lista de grupos de rutas para Ziggy
     * Los grupos se definen en RouteServiceProvider
     */
    'groups' => [
        'public' => [], // Rutas accesibles para usuarios no autenticados
        'staff' => [], // Rutas para el panel interno de personal
    ],

    /**
     * Si es falso, ninguna ruta se incluirá a menos que coincida explícitamente con uno de los patrones de solo/excepto.
     * Si es verdadero, todas las rutas serán incluidas a menos que se excluyan explícitamente.
     */
    'only' => [],
    'except' => [],

    /**
     * La clave dentro de la configuración de sesión para almacenar cualquier mensaje de estado.
     */
    'skip-route-function' => false,
];
