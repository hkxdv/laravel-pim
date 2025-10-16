<?php

declare(strict_types=1);

return [
    // Configuración básica del módulo
    'functional_name' => 'Módulo genérico 1',
    'description' => 'Módulo genérico para demostración.',
    'module_slug' => 'module01',
    'inertia_view_directory' => 'module01',
    'base_permission' => 'access-module-01',

    // Configuración del ítem de navegación principal
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.module01.index',
        'icon' => 'ClipboardList',
    ],

    // Configuración de navegación contextual (mínima)
    'contextual_nav' => [
        'default' => [
            [
                'title' => 'Panel de ejemplo',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-module-01',
            ],
        ],
    ],

    // Configuración de ítems del panel (mínima)
    'panel_items' => [
        [
            'name' => 'Item de ejemplo 1',
            'description' => 'Item de ejemplo 1 para la demostración del proyecto.',
            'route_name_suffix' => 'index',
            'icon' => 'FilePlus2',
            'permission' => 'access-module-01',
        ],
    ],
];
