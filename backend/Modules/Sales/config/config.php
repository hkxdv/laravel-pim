<?php

declare(strict_types=1);

return [
    'module_slug' => 'sales',
    'auth_guard' => 'staff',
    'functional_name' => 'Ventas y Pedidos',
    'description' => 'Gestión de órdenes, entregas y reportes básicos.',
    'inertia_view_directory' => 'sales',
    'base_permission' => 'access-sales',
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.sales.index',
        'icon' => 'FilePlus2',
    ],
    'nav_components' => [
        'links' => [
            'panel' => [
                'title' => 'Panel de Control',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'access-sales',
            ],
            'orders_list' => [
                'title' => 'Órdenes',
                'route_name_suffix' => 'orders.list',
                'icon' => 'ListOrdered',
                'permission' => 'access-sales',
            ],
            'reports_top_products' => [
                'title' => 'Top productos',
                'route_name_suffix' => 'reports.top-products',
                'icon' => 'BarChart3',
                'permission' => 'access-sales',
            ],
            'reports_stock_outs' => [
                'title' => 'Agotamientos',
                'route_name_suffix' => 'reports.stock-outs',
                'icon' => 'AlertTriangle',
                'permission' => 'access-sales',
            ],
            'back_to_panel' => [
                'title' => 'Volver al panel',
                'route_name_suffix' => 'index',
                'icon' => 'SquareChevronLeft',
                'permission' => 'access-sales',
            ],
            'back_to_orders' => [
                'title' => 'Volver a órdenes',
                'route_name_suffix' => 'orders.list',
                'icon' => 'SquareChevronLeft',
                'permission' => 'access-sales',
            ],
        ],
        'groups' => [
            'module_panel_nav' => [
                '$ref:nav_components.links.orders_list',
                '$ref:nav_components.links.reports_top_products',
                '$ref:nav_components.links.reports_stock_outs',
            ],
            'back_navigation' => [
                '$ref:nav_components.links.back_to_panel',
            ],
        ],
    ],
    'contextual_nav' => [
        'default' => [
            '$ref:nav_components.groups.module_panel_nav',
        ],
        'orders.detail' => [
            '$ref:nav_components.links.back_to_orders',
        ],
    ],
    'panel_items' => [
        [
            'name' => 'Órdenes',
            'description' => 'Gestión de órdenes y estados.',
            'route_name_suffix' => 'orders.list',
            'icon' => 'ListOrdered',
            'permission' => 'access-sales',
        ],
        [
            'name' => 'Top productos',
            'description' => 'Ranking por cantidad vendida.',
            'route_name_suffix' => 'reports.top-products',
            'icon' => 'BarChart3',
            'permission' => 'access-sales',
        ],
        [
            'name' => 'Agotamientos',
            'description' => 'Eventos de stock en cero por mes.',
            'route_name_suffix' => 'reports.stock-outs',
            'icon' => 'AlertTriangle',
            'permission' => 'access-sales',
        ],
    ],
    'breadcrumb_components' => [
        'sales_root' => [
            'title' => 'Ventas y Pedidos',
            'route_name_suffix' => 'index',
        ],
        'orders_list' => [
            'title' => 'Órdenes',
            'route_name_suffix' => 'orders.list',
        ],
        'order_detail' => [
            'title' => 'Detalle de orden',
            'route_name_suffix' => 'orders.detail',
            'dynamic_title_prop' => 'order.id',
        ],
        'reports_top_products' => [
            'title' => 'Top productos',
            'route_name_suffix' => 'reports.top-products',
        ],
        'reports_stock_outs' => [
            'title' => 'Agotamientos',
            'route_name_suffix' => 'reports.stock-outs',
        ],
    ],
    'breadcrumbs' => [
        'default' => [
            '$ref:breadcrumb_components.sales_root',
        ],
        'orders.list' => [
            '$ref:breadcrumb_components.sales_root',
            '$ref:breadcrumb_components.orders_list',
        ],
        'orders.detail' => [
            '$ref:breadcrumb_components.sales_root',
            '$ref:breadcrumb_components.orders_list',
            '$ref:breadcrumb_components.order_detail',
        ],
        'reports.top-products' => [
            '$ref:breadcrumb_components.sales_root',
            '$ref:breadcrumb_components.reports_top_products',
        ],
        'reports.stock-outs' => [
            '$ref:breadcrumb_components.sales_root',
            '$ref:breadcrumb_components.reports_stock_outs',
        ],
    ],
];
