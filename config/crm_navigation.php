<?php

return [
    'panels' => [
        'super_admin' => [
            ['group' => 'Dashboard', 'label' => 'Overview', 'route' => '/super-admin'],
            ['group' => 'Tenant Management', 'label' => 'All Tenants', 'route' => '/super-admin/tenants'],
            ['group' => 'Tenant Management', 'label' => 'Organizations', 'route' => '/super-admin/organizations'],
            ['group' => 'User Management', 'label' => 'All Users', 'route' => '/super-admin/users'],
            ['group' => 'System', 'label' => 'Audit Logs', 'route' => '/super-admin/audit-logs'],
            ['group' => 'System', 'label' => 'Global Settings', 'route' => '/super-admin/settings'],
        ],
        'org_admin' => [
            ['group' => 'Dashboard', 'label' => 'Overview', 'route' => '/admin/{tenant}'],
            ['group' => 'Structure', 'label' => 'Organizations', 'route' => '/admin/{tenant}/organizations'],
            ['group' => 'Structure', 'label' => 'Manufacturers', 'route' => '/admin/{tenant}/manufacturers'],
            ['group' => 'Structure', 'label' => 'Distributors', 'route' => '/admin/{tenant}/distributors'],
            ['group' => 'Structure', 'label' => 'Vendors', 'route' => '/admin/{tenant}/vendors'],
            ['group' => 'Catalog', 'label' => 'Products', 'route' => '/admin/{tenant}/products'],
            ['group' => 'Operations', 'label' => 'Inventory', 'route' => '/admin/{tenant}/inventory'],
            ['group' => 'Sales', 'label' => 'Orders', 'route' => '/admin/{tenant}/orders'],
            ['group' => 'Sales', 'label' => 'Quotations', 'route' => '/admin/{tenant}/quotations'],
            ['group' => 'Sales', 'label' => 'Invoices', 'route' => '/admin/{tenant}/invoices'],
            ['group' => 'Finance', 'label' => 'Commission Rules', 'route' => '/admin/{tenant}/commissions'],
            ['group' => 'Finance', 'label' => 'Commission Ledger', 'route' => '/admin/{tenant}/commission-ledger'],
        ],
        'manufacturer' => [
            ['group' => 'Dashboard', 'label' => 'Overview', 'route' => '/admin/{tenant}'],
            ['group' => 'My Network', 'label' => 'Distributors', 'route' => '/admin/{tenant}/distributors'],
            ['group' => 'Catalog', 'label' => 'Products', 'route' => '/admin/{tenant}/products'],
            ['group' => 'Operations', 'label' => 'Inventory', 'route' => '/admin/{tenant}/inventory'],
            ['group' => 'Sales', 'label' => 'Orders', 'route' => '/admin/{tenant}/orders'],
            ['group' => 'Sales', 'label' => 'Invoices', 'route' => '/admin/{tenant}/invoices'],
        ],
        'distributor' => [
            ['group' => 'Dashboard', 'label' => 'Overview', 'route' => '/admin/{tenant}'],
            ['group' => 'My Network', 'label' => 'Vendors', 'route' => '/admin/{tenant}/vendors'],
            ['group' => 'Catalog', 'label' => 'Products', 'route' => '/admin/{tenant}/products'],
            ['group' => 'Sales', 'label' => 'Quotations', 'route' => '/admin/{tenant}/quotations?tab=received'],
            ['group' => 'Sales', 'label' => 'Orders', 'route' => '/admin/{tenant}/orders'],
            ['group' => 'Sales', 'label' => 'Invoices', 'route' => '/admin/{tenant}/invoices'],
        ],
        'vendor' => [
            ['group' => 'Dashboard', 'label' => 'Overview', 'route' => '/admin/{tenant}'],
            ['group' => 'My Network', 'label' => 'Consumers', 'route' => '/admin/{tenant}/consumers'],
            ['group' => 'Catalog', 'label' => 'Products', 'route' => '/admin/{tenant}/products'],
            ['group' => 'Sales', 'label' => 'My Quotations', 'route' => '/admin/{tenant}/quotations'],
            ['group' => 'Sales', 'label' => 'Orders', 'route' => '/admin/{tenant}/orders'],
            ['group' => 'Sales', 'label' => 'Invoices', 'route' => '/admin/{tenant}/invoices'],
        ],
        'consumer' => [
            ['group' => 'Dashboard', 'label' => 'Overview', 'route' => '/admin/{tenant}'],
            ['group' => 'Shopping', 'label' => 'Browse Products', 'route' => '/admin/{tenant}/products'],
            ['group' => 'Shopping', 'label' => 'My Orders', 'route' => '/admin/{tenant}/orders'],
            ['group' => 'Shopping', 'label' => 'My Invoices', 'route' => '/admin/{tenant}/invoices'],
            ['group' => 'Account', 'label' => 'Profile', 'route' => '/admin/{tenant}/profile'],
        ],
    ],
];
