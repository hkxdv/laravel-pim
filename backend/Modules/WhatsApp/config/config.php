<?php

declare(strict_types=1);

return [
    'name' => 'WhatsApp',
    'module_slug' => 'whatsapp',
    'auth_guard' => 'staff',
    'base_permission' => 'access-whatsapp',
    'functional_name' => 'Mensajería WhatsApp',
    'description' => 'Comunicación rápida basada en plantillas de Twilio y registro de actividad.',
    'inertia_view_directory' => 'module03',

    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.whatsapp.index',
        'icon' => 'FileText',
    ],

    'nav_components' => [
        'links' => [
            'panel' => [
                'title' => 'Panel',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-whatsapp',
            ],
            'logs_list' => [
                'title' => 'Logs del agente',
                'route_name_suffix' => 'logs.index',
                'icon' => 'FileText',
                'permission' => 'access-whatsapp',
            ],
            'back_to_panel' => [
                'title' => 'Volver al panel',
                'route_name_suffix' => 'index',
                'icon' => 'SquareChevronLeft',
                'permission' => 'access-whatsapp',
            ],
        ],

        'groups' => [
            'module_panel_nav' => [
                '$ref:nav_components.links.panel',
                '$ref:nav_components.links.logs_list',
            ],
            'back_navigation' => [
                '$ref:nav_components.links.back_to_panel',
            ],
        ],
    ],

    'contextual_nav' => [
        'default' => [
            '$ref:nav_components.groups.module_panel_nav',
            '$ref:nav_components.groups.back_navigation',
        ],
    ],

    'panel_items' => [
        [
            'name' => 'Logs del agente',
            'description' => 'Registros de actividad del agente.',
            'route_name_suffix' => 'logs.index',
            'icon' => 'FileText',
            'permission' => 'access-whatsapp',
        ],
    ],

    'breadcrumb_components' => [
        'whatsapp_root' => [
            'title' => 'WhatsApp',
            'route_name_suffix' => 'index',
        ],
        'logs_list' => [
            'title' => 'Logs del agente',
            'route_name_suffix' => 'logs.index',
        ],
    ],

    'breadcrumbs' => [
        'default' => [
            '$ref:breadcrumb_components.whatsapp_root',
        ],
        'logs.index' => [
            '$ref:breadcrumb_components.whatsapp_root',
            '$ref:breadcrumb_components.logs_list',
        ],
    ],
];
