<?php

declare(strict_types=1);

return [
    'menu' => [
        'admin' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'bi bi-speedometer2',
                'href' => '/admin/dashboard',
                'activeRoot' => true,
                'activeSubstrings' => ['/admin/dashboard'],
            ],
            [
                'key' => 'config-tienda',
                'label' => 'Configuración',
                'icon' => 'bi bi-gear',
                'href' => '/admin/configuracion/tienda',
                'activeSubstrings' => ['/admin/configuracion/tienda'],
            ],
            [
                'key' => 'config-usuarios',
                'label' => 'Usuarios',
                'icon' => 'bi bi-person-badge',
                'href' => '/admin/configuracion/usuarios',
                'activeSubstrings' => ['/admin/configuracion/usuarios'],
                'roles' => ['admin'],
            ],
            [
                'key' => 'productos',
                'label' => 'Productos',
                'icon' => 'bi bi-box-seam',
                'href' => '/admin/productos',
                'activeSubstrings' => ['/admin/productos'],
            ],
            [
                'key' => 'categorias',
                'label' => 'Categorias',
                'icon' => 'bi bi-tags',
                'href' => '/admin/categorias',
                'activeSubstrings' => ['/admin/categorias'],
            ],
            [
                'key' => 'clientes',
                'label' => 'Clientes',
                'icon' => 'bi bi-people',
                'href' => '/admin/clientes',
                'activeSubstrings' => ['/admin/clientes'],
            ],
            [
                'key' => 'caja',
                'label' => 'Caja',
                'icon' => 'bi bi-cash-coin',
                'href' => '/admin/caja',
                'activeSubstrings' => ['/admin/caja'],
            ],
            [
                'key' => 'ventas',
                'label' => 'Ventas',
                'icon' => 'bi bi-receipt-cutoff',
                'children' => [
                    [
                        'label' => 'Historial',
                        'href' => '/admin/ventas',
                        'activeSubstrings' => ['/admin/ventas'],
                    ],
                    [
                        'label' => 'Cotizaciones',
                        'href' => '/admin/cotizaciones',
                        'activeSubstrings' => ['/admin/cotizaciones'],
                    ],
                    [
                        'label' => 'Apartados',
                        'href' => '/admin/apartados',
                        'activeSubstrings' => ['/admin/apartados'],
                    ],
                ],
            ],
            [
                'key' => 'gastos',
                'label' => 'Gastos',
                'icon' => 'bi bi-wallet2',
                'href' => '/admin/gastos',
                'activeSubstrings' => ['/admin/gastos'],
            ],
            [
                'key' => 'reportes',
                'label' => 'Reportes',
                'icon' => 'bi bi-bar-chart-line',
                'href' => '/admin/reportes',
                'activeSubstrings' => ['/admin/reportes'],
            ],

            // Hito 10: lineas futuras (placeholders)
            [
                'key' => 'compras',
                'label' => 'Compras',
                'icon' => 'bi bi-truck',
                'href' => '/admin/futuro/compras',
                'activeSubstrings' => ['/admin/futuro/compras'],
            ],
            [
                'key' => 'apartados',
                'label' => 'Apartados',
                'icon' => 'bi bi-archive',
                'href' => '/admin/apartados',
                'activeSubstrings' => ['/admin/apartados'],
            ],
            [
                'key' => 'facturacion',
                'label' => 'Facturacion',
                'icon' => 'bi bi-file-earmark-text',
                'href' => '/admin/futuro/facturacion',
                'activeSubstrings' => ['/admin/futuro/facturacion'],
            ],
            [
                'key' => 'tienda',
                'label' => 'Tienda en linea',
                'icon' => 'bi bi-shop-window',
                'href' => 'https://mimostrador.com/onepiece/public/catalogo/tienda-principal',
                'activeSubstrings' => [],
            ],
            [
                'key' => 'impresion',
                'label' => 'Impresion avanzada',
                'icon' => 'bi bi-printer',
                'href' => '/admin/futuro/impresion',
                'activeSubstrings' => ['/admin/futuro/impresion'],
            ],
            [
                'key' => 'lotes',
                'label' => 'Lotes y caducidades',
                'icon' => 'bi bi-hourglass-split',
                'href' => '/admin/lotes',
                'activeSubstrings' => ['/admin/lotes', '/admin/futuro/lotes'],
            ],
            [
                'key' => 'sucursales',
                'label' => 'Multi-sucursal',
                'icon' => 'bi bi-buildings',
                'href' => '/admin/futuro/sucursales',
                'activeSubstrings' => ['/admin/futuro/sucursales'],
            ],
            [
                'key' => 'dashboards',
                'label' => 'Dashboards ejecutivos',
                'icon' => 'bi bi-graph-up',
                'href' => '/admin/futuro/dashboards',
                'activeSubstrings' => ['/admin/futuro/dashboards'],
            ],
        ],
    ],
];

