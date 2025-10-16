<?php

declare(strict_types=1);

return [
    'functional_name' => 'Módulo genérico 2',
    'description' => 'Módulo genérico para demostración.',
    'module_slug' => 'module02',
    'inertia_view_directory' => 'module02',
    'base_permission' => 'access-module-02',
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.module02.index',
        'icon' => 'FilePlus2',
    ],
    'nav_components' => [
        'links' => [
            'panel' => [
                'title' => 'Panel de Control',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-module-02',
            ],
            'back_to_panel' => [
                'title' => 'Volver al panel',
                'route_name_suffix' => 'index',
                'icon' => 'SquareChevronLeft',
                'permission' => 'access-module-02',
            ],
        ],
    ],
    'contextual_nav' => [
        'default' => [
            [
                'title' => 'Panel de ejemplo',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-module-02',
            ],
        ],
    ],
    'panel_items' => [
        [
            'name' => 'Item de ejemplo 1',
            'description' => 'Item de ejemplo 1 para la demostración del proyecto.',
            'route_name_suffix' => 'index',
            'icon' => 'FilePlus2',
            'permission' => 'access-module-02',
        ],
    ],
];
