<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DashboardReportPdfService
{
    /**
     * @return array{path:string,filename:string}
     */
    public function generateForOrganization(Organization $organization): array
    {
        $outputDir = storage_path('app/tmp/reports');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $stats = [
            'orders_total' => Order::query()->whereHas('vendor', fn ($q) => $q->where('organization_id', $organization->id))->count(),
            'orders_delivered' => Order::query()->where('status', 'delivered')->whereHas('vendor', fn ($q) => $q->where('organization_id', $organization->id))->count(),
            'invoices_total' => (float) Invoice::query()->whereHas('quotation.vendor', fn ($q) => $q->where('organization_id', $organization->id))->sum('grand_total'),
            'invoices_paid' => (float) Invoice::query()->where('status', 'paid')->whereHas('quotation.vendor', fn ($q) => $q->where('organization_id', $organization->id))->sum('received_amount'),
        ];

        $base = 'sales-report-'.$organization->id.'-'.Str::random(8);
        $htmlPath = $outputDir.'/'.$base.'.html';
        $pdfPath = $outputDir.'/'.$base.'.pdf';

        file_put_contents($htmlPath, view('pdf.dashboard-report', [
            'organization' => $organization,
            'stats' => $stats,
            'generatedAt' => now()->toDateTimeString(),
        ])->render());

        $process = new Process([
            'libreoffice', '--headless', '--convert-to', 'pdf', '--outdir', $outputDir, $htmlPath,
        ]);
        $process->setTimeout(120);
        $process->run();
        @unlink($htmlPath);

        if (! $process->isSuccessful() || ! is_file($pdfPath)) {
            throw new RuntimeException('Unable to generate dashboard report PDF.');
        }

        return [
            'path' => $pdfPath,
            'filename' => 'sales-report-'.$organization->slug.'.pdf',
        ];
    }
}
