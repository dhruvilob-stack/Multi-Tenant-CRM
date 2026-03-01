<?php

use App\Services\DemoFlowService;
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
