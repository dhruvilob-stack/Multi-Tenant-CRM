<?php

return [
    'admin' => [
        'groups' => [
            'configuration' => 'Configuración',
            'my_network' => 'Mi Red',
            'settings' => 'Ajustes',
            'reports' => 'Reportes',
            'operations' => 'Operaciones',
        ],
        'pages' => [
            'profile' => [
                'nav' => 'Mi Perfil',
            ],
            'invitations' => [
                'expires' => 'vence',
                'empty' => 'Todavía no hay invitaciones enviadas.',
            ],
            'distributor_invitations' => [
                'nav' => 'Invitaciones Enviadas',
                'heading' => 'Invitaciones de Distribuidores',
            ],
            'manufacturer_invitations' => [
                'nav' => 'Invitaciones Enviadas',
                'heading' => 'Invitaciones de Fabricantes',
            ],
            'vendor_invitations' => [
                'nav' => 'Invitaciones Enviadas',
                'heading' => 'Invitaciones de Vendedores',
            ],
            'email_templates' => [
                'nav' => 'Plantillas de Correo',
                'heading' => 'Plantillas de Correo',
                'description' => 'Personaliza el contenido de correos de invitación, cotización y factura.',
            ],
            'reports_sales' => [
                'nav' => 'Reporte de Ventas',
                'heading' => 'Reporte de Ventas',
                'description' => 'Revisa la conversión de pedidos y los totales de facturas por rol y rango de fechas.',
            ],
            'reports_inventory' => [
                'nav' => 'Reporte de Inventario',
                'heading' => 'Reporte de Inventario',
                'description' => 'Inspecciona el stock, reservas y puntos de presión por bajo inventario.',
            ],
            'reports_commissions' => [
                'nav' => 'Reporte de Comisiones',
                'heading' => 'Reporte de Comisiones',
                'description' => 'Analiza la comisión acumulada por etapa de cadena y estado de pago.',
            ],
            'stock_movements' => [
                'nav' => 'Movimientos de Stock',
                'heading' => 'Movimientos de Stock',
                'description' => 'Rastrea transferencias y ajustes entre niveles de jerarquía.',
            ],
            'settings' => [
                'nav' => 'Ajustes del Sistema',
                'heading' => 'Ajustes del Sistema',
                'description' => 'Valores predeterminados de la organización para impuestos, moneda y facturación.',
            ],
        ],
    ],
    'super_admin' => [
        'groups' => [
            'system' => 'Sistema',
            'tenant_management' => 'Gestión de Inquilinos',
            'analytics' => 'Analítica',
            'user_management' => 'Gestión de Usuarios',
        ],
        'pages' => [
            'audit_logs' => [
                'nav' => 'Registros de Auditoría',
                'heading' => 'Registros de Auditoría',
                'description' => 'La auditoría global aparecerá aquí cuando el registro de actividad esté habilitado.',
            ],
            'email_templates' => [
                'nav' => 'Plantillas de Correo',
                'heading' => 'Plantillas de Correo',
                'description' => 'Gestiona plantillas para invitaciones, cotizaciones y facturas.',
            ],
            'plans' => [
                'nav' => 'Planes de Suscripción',
                'starter' => [
                    'heading' => 'Inicial',
                    'description' => 'Para nuevas organizaciones',
                    'price' => '$49 / mes',
                ],
                'growth' => [
                    'heading' => 'Crecimiento',
                    'description' => 'Para redes en expansión',
                    'price' => '$149 / mes',
                ],
                'enterprise' => [
                    'heading' => 'Empresarial',
                    'description' => 'Para cadenas grandes',
                    'price' => '$499 / mes',
                ],
            ],
            'reports_revenue' => [
                'nav' => 'Reportes de Ingresos',
                'heading' => 'Reportes de Ingresos',
                'description' => 'Sigue totales de facturas, saldos pagados y distribución de ingresos por inquilino.',
            ],
            'reports_usage' => [
                'nav' => 'Estadísticas de Uso',
                'heading' => 'Estadísticas de Uso',
                'description' => 'Monitorea inquilinos activos, usuarios por rol, conversión de cotizaciones y flujo de pedidos.',
            ],
            'roles' => [
                'nav' => 'Permisos de Roles',
                'heading' => 'Permisos de Roles',
                'description' => 'La matriz de permisos se aplica mediante recursos por rol y acciones de flujo.',
            ],
            'settings' => [
                'nav' => 'Ajustes Globales',
                'heading' => 'Ajustes Globales',
                'description' => 'Configura valores por defecto para impuestos, moneda, vencimiento de invitaciones y marca.',
            ],
        ],
    ],
];
