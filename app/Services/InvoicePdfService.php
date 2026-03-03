<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class InvoicePdfService
{
    /**
     * @return array{path: string, filename: string}
     */
    public function generate(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'items.product',
            'quotation.vendor.organization',
            'order.consumer',
        ]);

        $outputDir = storage_path('app/tmp/invoices');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $fileBase = sprintf('invoice-%s-%s', $invoice->id, Str::random(8));
        $htmlPath = $outputDir.'/'.$fileBase.'.html';
        $pdfPath = $outputDir.'/'.$fileBase.'.pdf';

        file_put_contents($htmlPath, view('pdf.invoice', [
            'invoice' => $invoice,
            'logoDataUri' => $this->resolveLogoDataUri($invoice),
            'vendorName' => $invoice->quotation?->vendor?->name,
            'organizationName' => $invoice->quotation?->vendor?->organization?->name,
        ])->render());

        $process = new Process([
            'libreoffice',
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $htmlPath,
        ]);

        $process->setTimeout(120);
        $process->run();

        @unlink($htmlPath);

        if (! $process->isSuccessful() || ! file_exists($pdfPath)) {
            throw new RuntimeException('Unable to generate invoice PDF.');
        }

        return [
            'path' => $pdfPath,
            'filename' => ($invoice->invoice_number ?: 'invoice').'.pdf',
        ];
    }

    private function resolveLogoDataUri(Invoice $invoice): ?string
    {
        $logo = $invoice->quotation?->vendor?->organization?->logo;
        if (! $logo) {
            return null;
        }

        $path = null;

        if (Storage::disk('public')->exists($logo)) {
            $path = Storage::disk('public')->path($logo);
        } elseif (file_exists(public_path($logo))) {
            $path = public_path($logo);
        }

        if (! $path || ! file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $binary = file_get_contents($path);

        if ($binary === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
