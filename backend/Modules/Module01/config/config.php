<?php

declare(strict_types=1);

return [
    // Configuración básica del módulo
    'module_slug' => 'module01',
    'auth_guard' => 'staff',
    'functional_name' => 'Inventario',
    'description' => 'Gestión de productos del inventario y operaciones básicas.',
    'inertia_view_directory' => 'module01',
    'base_permission' => 'access-module-01',

    // Configuración del ítem de navegación principal
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.module01.index',
        'icon' => 'ClipboardList',
    ],

    // Componentes reutilizables de navegación (bloques para construir la navegación)
    'nav_components' => [
        'links' => [
            'panel' => [
                'title' => 'Inventario',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-module-01',
            ],
            'products_list' => [
                'title' => 'Lista de Productos',
                'route_name_suffix' => 'products.index',
                'icon' => 'ScrollText',
                'permission' => 'access-module-01',
            ],
            'products_create' => [
                'title' => 'Crear Producto',
                'route_name_suffix' => 'products.create',
                'icon' => 'FilePlus2',
                'permission' => 'access-module-01',
            ],
            'back_to_panel' => [
                'title' => 'Volver al panel',
                'route_name_suffix' => 'index',
                'icon' => 'ArrowLeft',
                'permission' => 'access-module-01',
            ],
            'back_to_list' => [
                'title' => 'Volver a la lista',
                'route_name_suffix' => 'products.index',
                'icon' => 'ArrowLeft',
                'permission' => 'access-module-01',
            ],
            'stock_movements_list' => [
                'title' => 'Movimientos de Stock',
                'route_name_suffix' => 'stock_movements.index',
                'icon' => 'List',
                'permission' => 'access-module-01',
            ],
        ],

        // Grupos comunes de enlaces para reutilizar
        'groups' => [
            'module_panel_nav' => [
                '$ref:nav_components.links.products_list',
                '$ref:nav_components.links.stock_movements_list',
            ],
            'product_management' => [
                '$ref:nav_components.links.panel',
                '$ref:nav_components.links.products_list',
                '$ref:nav_components.links.products_create',
            ],
            'back_navigation' => [
                '$ref:nav_components.links.back_to_panel',
                '$ref:nav_components.links.back_to_list',
            ],
        ],
    ],

    // Configuración de navegación contextual
    'contextual_nav' => [
        'default' => [
            '$ref:nav_components.groups.product_management',
            '$ref:nav_components.links.stock_movements_list',
        ],
        'products.index' => [
            '$ref:nav_components.links.back_to_panel',
            '$ref:nav_components.links.products_create',
        ],
        'products.create' => ['$ref:nav_components.groups.back_navigation'],
        'products.edit' => [
            '$ref:nav_components.links.back_to_panel',
            '$ref:nav_components.links.back_to_list',
        ],
        'stock_movements.index' => [
            '$ref:nav_components.links.back_to_panel',
            '$ref:nav_components.links.products_list',
        ],
        'stock_movements.create' => [
            '$ref:nav_components.links.back_to_panel',
            '$ref:nav_components.links.stock_movements_list',
        ],
    ],

    // Configuración de ítems del panel
    'panel_items' => [
        [
            'name' => 'Lista de Productos',
            'description' => 'Añadir, editar o eliminar productos del inventario.',
            'route_name_suffix' => 'products.index',
            'icon' => 'Package',
            'permission' => 'access-module-01',
        ],
        [
            'name' => 'Movimientos de Stock',
            'description' => 'Ver y auditar movimientos de stock.',
            'route_name_suffix' => 'stock_movements.index',
            'icon' => 'List',
            'permission' => 'access-module-01',
        ],
    ],

    // Componentes reutilizables de breadcrumbs
    'breadcrumb_components' => [
        'module01_root' => [
            'title' => 'Inventario',
            'route_name_suffix' => 'index',
        ],
        'products_list' => [
            'title' => 'Lista de Productos',
            'route_name_suffix' => 'products.index',
        ],
        'products_create' => [
            'title' => 'Crear Producto',
            'route_name_suffix' => 'products.create',
        ],
        'products_edit' => [
            'title' => 'Editar Producto',
            'route_name_suffix' => 'products.edit',
            'dynamic_title_prop' => 'product.name',
        ],
        'stock_movements_list' => [
            'title' => 'Movimientos de Stock',
            'route_name_suffix' => 'stock_movements.index',
        ],
        'stock_movements_create' => [
            'title' => 'Registrar Movimiento',
            'route_name_suffix' => 'stock_movements.create',
        ],
    ],

    // Configuración de breadcrumbs para cada ruta
    'breadcrumbs' => [
        'default' => [
            '$ref:breadcrumb_components.module01_root',
        ],
        'products.index' => [
            '$ref:breadcrumb_components.module01_root',
            '$ref:breadcrumb_components.products_list',
        ],
        'products.create' => [
            '$ref:breadcrumb_components.module01_root',
            '$ref:breadcrumb_components.products_list',
            '$ref:breadcrumb_components.products_create',
        ],
        'products.edit' => [
            '$ref:breadcrumb_components.module01_root',
            '$ref:breadcrumb_components.products_list',
            '$ref:breadcrumb_components.products_edit',
        ],
        // breadcrumbs para listado de movimientos
        'stock_movements.index' => [
            '$ref:breadcrumb_components.module01_root',
            '$ref:breadcrumb_components.stock_movements_list',
        ],
        // breadcrumbs para creación de movimiento
        'stock_movements.create' => [
            '$ref:breadcrumb_components.module01_root',
            '$ref:breadcrumb_components.stock_movements_list',
            '$ref:breadcrumb_components.stock_movements_create',
        ],
    ],
];
