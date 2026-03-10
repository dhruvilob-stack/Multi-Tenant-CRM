<?php

return [
    'admin' => [
        'groups' => [
            'configuration' => 'Configuration',
            'my_network' => 'My Network',
            'settings' => 'Settings',
            'reports' => 'Reports',
            'operations' => 'Operations',
        ],
        'pages' => [
            'profile' => [
                'nav' => 'My Profile',
            ],
            'invitations' => [
                'expires' => 'expires',
                'empty' => 'No invitations sent yet.',
            ],
            'distributor_invitations' => [
                'nav' => 'Invitations Sent',
                'heading' => 'Distributor Invitations',
            ],
            'manufacturer_invitations' => [
                'nav' => 'Invitations Sent',
                'heading' => 'Manufacturer Invitations',
            ],
            'vendor_invitations' => [
                'nav' => 'Invitations Sent',
                'heading' => 'Vendor Invitations',
            ],
            'email_templates' => [
                'nav' => 'Email Templates',
                'heading' => 'Email Templates',
                'description' => 'Customize invitation, quotation, and invoice email bodies.',
            ],
            'reports_sales' => [
                'nav' => 'Sales Report',
                'heading' => 'Sales Report',
                'description' => 'Review order conversion and invoice totals by role and date range.',
            ],
            'reports_inventory' => [
                'nav' => 'Inventory Report',
                'heading' => 'Inventory Report',
                'description' => 'Inspect stock position, reserves, and low-stock pressure points.',
            ],
            'reports_commissions' => [
                'nav' => 'Commission Report',
                'heading' => 'Commission Report',
                'description' => 'Analyze accrued commission by chain step and payout status.',
            ],
            'stock_movements' => [
                'nav' => 'Stock Movements',
                'heading' => 'Stock Movements',
                'description' => 'Track transfers and adjustments between hierarchy levels.',
            ],
            'settings' => [
                'nav' => 'System Settings',
                'heading' => 'System Settings',
                'description' => 'Organization-wide defaults for tax, currency, and invoice behavior.',
            ],
        ],
    ],
    'super_admin' => [
        'groups' => [
            'system' => 'System',
            'tenant_management' => 'Tenant Management',
            'analytics' => 'Analytics',
            'user_management' => 'User Management',
        ],
        'pages' => [
            'audit_logs' => [
                'nav' => 'Audit Logs',
                'heading' => 'Audit Logs',
                'description' => 'Global audit trail appears here once activity logging is enabled.',
            ],
            'email_templates' => [
                'nav' => 'Email Templates',
                'heading' => 'Email Templates',
                'description' => 'Manage invitation, quotation, and invoice template content.',
            ],
            'plans' => [
                'nav' => 'Subscription Plans',
                'starter' => [
                    'heading' => 'Starter',
                    'description' => 'For new organizations',
                    'price' => '$49 / month',
                ],
                'growth' => [
                    'heading' => 'Growth',
                    'description' => 'For scaling networks',
                    'price' => '$149 / month',
                ],
                'enterprise' => [
                    'heading' => 'Enterprise',
                    'description' => 'For large chains',
                    'price' => '$499 / month',
                ],
            ],
            'reports_revenue' => [
                'nav' => 'Revenue Reports',
                'heading' => 'Revenue Reports',
                'description' => 'Track invoice totals, paid balances, and tenant-level revenue distribution.',
            ],
            'reports_usage' => [
                'nav' => 'Usage Stats',
                'heading' => 'Usage Stats',
                'description' => 'Monitor active tenants, users by role, quotation conversion, and order throughput.',
            ],
            'roles' => [
                'nav' => 'Role Permissions',
                'heading' => 'Role Permissions',
                'description' => 'Permission matrix is enforced through role-based resources and workflow actions.',
            ],
            'settings' => [
                'nav' => 'Global Settings',
                'heading' => 'Global Settings',
                'description' => 'Configure defaults for Maintenance Mode, currency, Timezone, and panel branding.',
            ],
        ],
    ],
];
