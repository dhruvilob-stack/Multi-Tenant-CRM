<?php

use App\Services\DemoFlowService;
use App\Models\Invoice;
use App\Models\CommissionLedger;
use App\Services\CommissionService;
use App\Services\PartnerWalletService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('crm:seed-demo', function (DemoFlowService $demoFlowService) {
    $data = $demoFlowService->seedDemoData();

    $this->info('Demo network seeded.');
    $this->table(['Key', 'Value'], collect($data)->map(fn ($v, $k) => [$k, $v])->all());
})->purpose('Seed demo hierarchy data for CRM flow');

Artisan::command('crm:run-demo-flow', function (DemoFlowService $demoFlowService) {
    $data = $demoFlowService->runFlow();

    $this->info('Demo flow completed: invitation -> quotation -> invoice -> paid -> commissions.');
    $this->table(['Key', 'Value'], [
        ['Quotation Number', $data['quotation_number']],
        ['Quotation Status', $data['quotation_status']],
        ['Invoice Number', $data['invoice_number']],
        ['Invoice Status', $data['invoice_status']],
        ['Commission Entries', $data['commission_entries']],
        ['Super Admin', $data['routes']['super_admin_dashboard']],
        ['Tenant Dashboard', $data['routes']['tenant_dashboard']],
        ['Quotation Route', $data['routes']['quotation']],
        ['Invoice Route', $data['routes']['invoice']],
    ]);
})->purpose('Execute complete CRM demo business flow');

Artisan::command('crm:rebuild-commissions', function (CommissionService $commissionService, PartnerWalletService $walletService) {
    $this->info('Rebuilding commission ledger from invoices...');

    $processed = 0;
    $generated = 0;
    $cleared = 0;

    Invoice::withoutGlobalScopes()
        ->with(['items', 'order.vendor', 'quotation.vendor'])
        ->orderBy('id')
        ->chunkById(100, function ($invoices) use ($commissionService, &$processed, &$generated, &$cleared): void {
            foreach ($invoices as $invoice) {
                $processed++;

                $before = CommissionLedger::withoutGlobalScopes()
                    ->where('invoice_id', $invoice->id)
                    ->count();

                $commissionService->syncForInvoice($invoice);

                $after = CommissionLedger::withoutGlobalScopes()
                    ->where('invoice_id', $invoice->id)
                    ->count();

                if ($after > 0) {
                    $generated++;
                } elseif ($before > 0 && $after === 0) {
                    $cleared++;
                }
            }
        });

    $allFromUserIds = CommissionLedger::withoutGlobalScopes()
        ->whereNotNull('from_user_id')
        ->pluck('from_user_id')
        ->map(fn ($id): int => (int) $id)
        ->unique()
        ->values()
        ->all();

    $walletService->syncForUsers($allFromUserIds);

    $this->table(['Metric', 'Value'], [
        ['Invoices Processed', (string) $processed],
        ['Invoices With Ledger', (string) $generated],
        ['Invoices Cleared', (string) $cleared],
        ['Wallets Synced', (string) count($allFromUserIds)],
    ]);
})->purpose('Rebuild commission ledgers and partner wallets from current invoices');
