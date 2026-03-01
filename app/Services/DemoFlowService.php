<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CommissionLedger;
use App\Models\Invitation;
use App\Models\MarginCommission;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoFlowService
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly QuotationWorkflowService $quotationWorkflowService,
        private readonly InvoiceWorkflowService $invoiceWorkflowService,
        private readonly CommissionService $commissionService,
    ) {
    }

    public function seedDemoData(): array
    {
        return DB::transaction(function (): array {
            $tenant = Tenant::query()->firstOrCreate(
                ['id' => '11111111-1111-1111-1111-111111111111'],
                [
                    'name' => 'Demo Tenant',
                    'domain' => 'demo.local',
                    'data' => ['plan' => 'enterprise'],
                ]
            );

            $organization = Organization::query()->firstOrCreate(
                ['slug' => 'demo-org'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Demo Organization',
                    'email' => 'org-admin@demo.local',
                    'phone' => '+1-555-0100',
                    'status' => 'active',
                ]
            );

            $orgAdmin = User::query()->firstOrCreate(
                ['email' => 'org-admin@demo.local'],
                [
                    'name' => 'Demo Org Admin',
                    'password' => 'password',
                    'role' => UserRole::ORG_ADMIN,
                    'organization_id' => $organization->id,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $orgAdmin->organizations()->syncWithoutDetaching([$organization->id]);

            $manufacturer = $this->upsertUser('manufacturer@demo.local', 'Demo Manufacturer', UserRole::MANUFACTURER, $organization->id, $orgAdmin->id);
            $distributor = $this->upsertUser('distributor@demo.local', 'Demo Distributor', UserRole::DISTRIBUTOR, $organization->id, $manufacturer->id);
            $vendor = $this->upsertUser('vendor@demo.local', 'Demo Vendor', UserRole::VENDOR, $organization->id, $distributor->id);

            $consumer = User::query()->where('email', 'consumer@demo.local')->first();
            if (! $consumer) {
                $token = $this->invitationService->generateToken(
                    'consumer@demo.local',
                    $organization->id,
                    UserRole::CONSUMER,
                    $vendor->id
                );

                Invitation::query()->create([
                    'inviter_id' => $vendor->id,
                    'invitee_email' => 'consumer@demo.local',
                    'role' => UserRole::CONSUMER,
                    'token' => $token,
                    'organization_id' => $organization->id,
                    'expires_at' => now()->addHours(72),
                ]);

                $consumer = $this->invitationService->acceptInvitation($token, 'Demo Consumer', 'password');
            }

            $consumer->organizations()->syncWithoutDetaching([$organization->id]);

            $category = Category::query()->firstOrCreate(
                ['slug' => 'electronics-demo'],
                ['organization_id' => $organization->id, 'name' => 'Electronics']
            );

            $product = Product::query()->firstOrCreate(
                ['sku' => 'DEMO-SKU-001'],
                [
                    'manufacturer_id' => $manufacturer->id,
                    'category_id' => $category->id,
                    'name' => 'Demo Router',
                    'description' => 'Enterprise WiFi router.',
                    'base_price' => 1000,
                    'unit' => 'pcs',
                    'status' => 'active',
                ]
            );

            $this->upsertCommissionRule($product->id, UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, 'percentage', 8);
            $this->upsertCommissionRule($product->id, UserRole::DISTRIBUTOR, UserRole::VENDOR, 'percentage', 5);
            $this->upsertCommissionRule($product->id, UserRole::VENDOR, UserRole::CONSUMER, 'percentage', 2);

            $quotation = Quotation::query()->firstOrCreate(
                ['quotation_number' => 'QUO-'.now()->format('Y').'-0001'],
                [
                    'vendor_id' => $vendor->id,
                    'distributor_id' => $distributor->id,
                    'status' => 'draft',
                    'subject' => 'Demo Network Equipment Quotation',
                    'valid_until' => now()->addDays(10)->toDateString(),
                    'subtotal' => 3600,
                    'discount_amount' => 100,
                    'tax_amount' => 350,
                    'grand_total' => 3850,
                ]
            );

            if (! $quotation->items()->exists()) {
                $quotation->items()->create([
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'qty' => 3,
                    'selling_price' => 1200,
                    'discount_percent' => 0,
                    'net_price' => 1200,
                    'total' => 3600,
                    'tax_rate' => 10,
                    'tax_amount' => 350,
                ]);
            }

            return [
                'tenant_id' => $tenant->id,
                'organization_id' => $organization->id,
                'manufacturer_id' => $manufacturer->id,
                'distributor_id' => $distributor->id,
                'vendor_id' => $vendor->id,
                'consumer_id' => $consumer->id,
                'quotation_id' => $quotation->id,
            ];
        });
    }

    public function runFlow(): array
    {
        $this->seedDemoData();

        return DB::transaction(function (): array {
            /** @var Quotation $quotation */
            $quotation = Quotation::query()->where('quotation_number', 'QUO-'.now()->format('Y').'-0001')->firstOrFail();

            if ($quotation->status === 'draft') {
                $this->quotationWorkflowService->send($quotation);
            }

            if ($quotation->status === 'sent') {
                $this->quotationWorkflowService->negotiate($quotation);
                $this->quotationWorkflowService->send($quotation->refresh());
            }

            $invoice = $quotation->invoice ?: $this->quotationWorkflowService->confirm($quotation->refresh(), 15);

            if (! $invoice->order_id) {
                $order = Order::query()->create([
                    'order_number' => sprintf('ORD-%s-%04d', now()->format('Y'), ((int) Order::query()->max('id')) + 1),
                    'consumer_id' => User::query()->where('email', 'consumer@demo.local')->value('id'),
                    'vendor_id' => User::query()->where('email', 'vendor@demo.local')->value('id'),
                    'invoice_id' => $invoice->id,
                    'status' => 'confirmed',
                    'total_amount' => $invoice->grand_total,
                ]);
                $invoice->update(['order_id' => $order->id]);
            }

            $invoice = $this->invoiceWorkflowService->approve($invoice->refresh());
            $invoice = $this->invoiceWorkflowService->markPaid($invoice, (float) $invoice->grand_total);

            $this->commissionService->generateForInvoice($invoice->refresh());

            return [
                'quotation_number' => $quotation->quotation_number,
                'quotation_status' => $quotation->refresh()->status,
                'invoice_number' => $invoice->invoice_number,
                'invoice_status' => $invoice->status,
                'commission_entries' => CommissionLedger::query()->where('invoice_id', $invoice->id)->count(),
                'routes' => [
                    'super_admin_dashboard' => '/super-admin',
                    'tenant_dashboard' => '/admin/'.$invoice->quotation->vendor->organization->slug,
                    'quotation' => '/admin/'.$invoice->quotation->vendor->organization->slug.'/quotations/'.$quotation->id,
                    'invoice' => '/admin/'.$invoice->quotation->vendor->organization->slug.'/invoices/'.$invoice->id,
                ],
            ];
        });
    }

    private function upsertUser(string $email, string $name, string $role, int $organizationId, int $parentId): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'organization_id' => $organizationId,
                'parent_id' => $parentId,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $user->organizations()->syncWithoutDetaching([$organizationId]);

        return $user;
    }

    private function upsertCommissionRule(int $productId, string $fromRole, string $toRole, string $type, float $value): void
    {
        MarginCommission::query()->updateOrCreate(
            [
                'product_id' => $productId,
                'from_role' => $fromRole,
                'to_role' => $toRole,
            ],
            [
                'commission_type' => $type,
                'commission_value' => $value,
            ]
        );
    }
}
