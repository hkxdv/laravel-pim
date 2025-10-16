<?php

declare(strict_types=1);

return [
    // Configuración básica del módulo
    'module_slug' => 'admin',
    'auth_guard' => 'staff',
    'functional_name' => 'Módulo de Administración',
    'description' => 'Explora las opciones de administración del sistema y revisa las estadísticas clave.',
    'base_permission' => 'access-admin',

    // Configuración del ítem de navegación principal
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.admin.panel',
        'icon' => 'ShieldCheck',
    ],

    // Componentes reutilizables de navegación (bloques para construir la navegación)
    'nav_components' => [
        // Enlaces individuales reutilizables
        'links' => [
            'panel' => [
                'title' => 'Módulo de Administración',
                'route_name_suffix' => 'panel',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-admin',
            ],
            'users_list' => [
                'title' => 'Lista de Usuarios',
                'route_name_suffix' => 'users.index',
                'icon' => 'ScrollText',
                'permission' => 'access-admin',
            ],
            'users_create' => [
                'title' => 'Crear Usuario',
                'route_name_suffix' => 'users.create',
                'icon' => 'UserPlus',
                'permission' => 'access-admin',
            ],
            'back_to_panel' => [
                'title' => 'Volver al panel',
                'route_name_suffix' => 'panel',
                'icon' => 'ArrowLeft',
                'permission' => 'access-admin',
            ],
            'back_to_list' => [
                'title' => 'Volver a la lista',
                'route_name_suffix' => 'users.index',
                'icon' => 'ArrowLeft',
                'permission' => 'access-admin',
            ],
        ],

        // Grupos comunes de enlaces para reutilizar
        'groups' => [
            'admin_panel_nav' => [
                '$ref:nav_components.links.users_list',
            ],
            'user_management' => [
                '$ref:nav_components.links.panel',
                '$ref:nav_components.links.users_list',
                '$ref:nav_components.links.users_create',
            ],
            'back_navigation' => [
                '$ref:nav_components.links.back_to_panel',
                '$ref:nav_components.links.back_to_list',
            ],
        ],
    ],

    // Configuración de navegación contextual
    'contextual_nav' => [
        'default' => ['$ref:nav_components.groups.user_management'],

        // Rutas para la gestión de usuarios
        'users.index' => [
            '$ref:nav_components.links.back_to_panel',
            '$ref:nav_components.links.users_create',
        ],
        'users.create' => ['$ref:nav_components.groups.back_navigation'],
        'users.edit' => [
            '$ref:nav_components.links.back_to_panel',
            '$ref:nav_components.links.back_to_list',
        ],

    ],

    // Configuración de ítems del panel
    'panel_items' => [
        [
            'name' => 'Lista de Usuarios',
            'description' => 'Añadir, editar o eliminar cuentas de usuario.',
            'route_name_suffix' => 'users.index',
            'icon' => 'Users',
            'permission' => 'access-admin',
        ],
    ],

    // Componentes reutilizables de breadcrumbs
    'breadcrumb_components' => [
        'admin_root' => [
            'title' => 'Módulo de Administración',
            'route_name_suffix' => 'panel',
        ],
        'users_list' => [
            'title' => 'Lista de Usuarios',
            'route_name_suffix' => 'users.index',
        ],
        'users_create' => [
            'title' => 'Crear Usuario',
            'route_name_suffix' => 'users.create',
        ],
        'users_edit' => [
            'title' => 'Editar Usuario',
            'route_name_suffix' => 'users.edit',
            'dynamic_title_prop' => 'user.name',
        ],
    ],

    // Configuración de breadcrumbs para cada ruta
    'breadcrumbs' => [
        'default' => [
            '$ref:breadcrumb_components.admin_root',
        ],
        'users.index' => [
            '$ref:breadcrumb_components.admin_root',
            '$ref:breadcrumb_components.users_list',
        ],
        'users.create' => [
            '$ref:breadcrumb_components.admin_root',
            '$ref:breadcrumb_components.users_list',
            '$ref:breadcrumb_components.users_create',
        ],
        'users.edit' => [
            '$ref:breadcrumb_components.admin_root',
            '$ref:breadcrumb_components.users_list',
            '$ref:breadcrumb_components.users_edit',
        ],
    ],
];
