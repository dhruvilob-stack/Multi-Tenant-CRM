# CRM Demo Flow (End-to-End)

## 1) Prerequisites
- MySQL must be running and reachable by `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- Run:
```bash
php artisan migrate
php artisan db:seed
```

## 2) Seed Demo Network
This creates tenant, organization, role hierarchy users, category/product, commission rules, and draft quotation:
```bash
php artisan crm:seed-demo
```

Demo users:
- `superadmin@example.com` (super_admin)
- `org-admin@demo.local` (org_admin)
- `manufacturer@demo.local` (manufacturer)
- `distributor@demo.local` (distributor)
- `vendor@demo.local` (vendor)
- `consumer@demo.local` (consumer)

Password for demo accounts: `password`

## 3) Run Complete Business Flow
This executes:
1. Invitation accept flow (consumer creation)
2. Quotation `draft -> sent -> negotiated -> sent -> confirmed -> converted`
3. Auto invoice creation from quotation
4. Invoice `approved -> paid`
5. Commission ledger generation

Command:
```bash
php artisan crm:run-demo-flow
```

## 4) HTTP Demo Endpoints
- `GET /demo/flow`:
Runs full flow and returns IDs, statuses, and key routes.

- `GET /demo/navigation`:
Returns role-wise navigation tabs and major route map.

## 5) Public Invitation Endpoints
- `GET /invitation/{token}`
- `GET /invitation/{token}/verify`
- `POST /invitation/{token}/set-password`

## 6) Panel Routes
- Super Admin panel: `/super-admin`
- Tenant panel (Filament tenancy slug): `/admin/{tenant}`

## 7) Navigation Tabs by Role
Configured in `config/crm_navigation.php`.

- `super_admin`: tenants, organizations, users, audit logs, settings
- `org_admin`: full structure, catalog, operations, sales, finance
- `manufacturer`: distributors, products, inventory, orders, invoices
- `distributor`: vendors, products, received quotations, orders, invoices
- `vendor`: consumers, products, quotations, orders, invoices
- `consumer`: browse products, own orders, own invoices, profile

## 8) Primary Business Route Sequence (Human Walkthrough)
1. Super admin logs in at `/super-admin`
2. Org admin works under `/admin/{tenant}`
3. Manufacturer invites distributor
4. Distributor invites vendor
5. Vendor invites consumer using `/invitation/{token}`
6. Vendor creates quotation at `/admin/{tenant}/quotations`
7. Distributor negotiates/confirms quotation
8. System converts to invoice at `/admin/{tenant}/invoices`
9. Finance marks invoice approved/sent/paid
10. Commission entries appear in commission ledger
