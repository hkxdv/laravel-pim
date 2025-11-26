<?php

declare(strict_types=1);

return [
    'module_slug' => 'assistant',
    'auth_guard' => 'staff',
    'functional_name' => 'Asistente',
    'description' => 'Herramientas de asistencia y orquestación.',
    'inertia_view_directory' => 'assistant',
    'base_permission' => 'access-assistant',

    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.assistant.index',
        'icon' => 'Bot',
    ],

    'nav_components' => [
        'links' => [
            'panel' => [
                'title' => 'Asistente',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-assistant',
            ],
        ],
        'groups' => [
            'module_panel_nav' => [
                '$ref:nav_components.links.panel',
            ],
        ],
    ],

    'contextual_nav' => [
        'default' => [
            '$ref:nav_components.groups.module_panel_nav',
        ],
    ],

    'panel_items' => [
        [
            'name' => 'Panel del Asistente',
            'description' => 'Accesos rápidos del asistente.',
            'route_name_suffix' => 'index',
            'icon' => 'Sparkles',
            'permission' => 'access-assistant',
        ],
    ],

    'breadcrumb_components' => [
        'assistant_root' => [
            'title' => 'Asistente',
            'route_name_suffix' => 'index',
        ],
    ],

    'breadcrumbs' => [
        'default' => [
            '$ref:breadcrumb_components.assistant_root',
        ],
    ],
];
