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

        $errors = [];

        if (! $this->tryGenerateWithLibreOffice($htmlPath, $pdfPath, $outputDir, $errors)
            && ! $this->tryGenerateWithHeadlessBrowser($htmlPath, $pdfPath, $errors)) {
            @unlink($htmlPath);
            $details = $errors === [] ? 'No PDF engine available.' : implode(' | ', $errors);

            throw new RuntimeException('Unable to generate invoice PDF. '.$details);
        }

        @unlink($htmlPath);

        return [
            'path' => $pdfPath,
            'filename' => ($invoice->invoice_number ?: 'invoice').'.pdf',
        ];
    }

    /**
     * @param array<int, string> $errors
     */
    private function tryGenerateWithLibreOffice(string $htmlPath, string $pdfPath, string $outputDir, array &$errors): bool
    {
        $binaries = [
            'libreoffice',
            'soffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
        ];

        foreach ($binaries as $binary) {
            $process = new Process([
                $binary,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDir,
                $htmlPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful() && file_exists($pdfPath)) {
                return true;
            }

            $error = trim($process->getErrorOutput().' '.$process->getOutput());
            if ($error !== '') {
                $errors[] = "LibreOffice({$binary}): ".preg_replace('/\s+/', ' ', $error);
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $errors
     */
    private function tryGenerateWithHeadlessBrowser(string $htmlPath, string $pdfPath, array &$errors): bool
    {
        $binaries = [
            'chrome',
            'chromium',
            'msedge',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        $fileUrl = 'file:///'.str_replace('\\', '/', realpath($htmlPath) ?: $htmlPath);

        foreach ($binaries as $binary) {
            $profileDir = storage_path('app/tmp/browser-profile-'.Str::random(8));
            if (! is_dir($profileDir)) {
                @mkdir($profileDir, 0775, true);
            }

            $process = new Process([
                $binary,
                '--headless=new',
                '--disable-gpu',
                '--disable-crash-reporter',
                '--no-first-run',
                '--no-default-browser-check',
                '--allow-file-access-from-files',
                '--user-data-dir='.$profileDir,
                '--print-to-pdf='.$pdfPath,
                $fileUrl,
            ]);
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful() && file_exists($pdfPath)) {
                $this->deleteDirectory($profileDir);
                return true;
            }

            $error = trim($process->getErrorOutput().' '.$process->getOutput());
            if ($error !== '') {
                $errors[] = "Browser({$binary}): ".preg_replace('/\s+/', ' ', $error);
            }

            $this->deleteDirectory($profileDir);
        }

        return false;
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = @scandir($path);
        if (! is_array($items)) {
            @rmdir($path);
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path.DIRECTORY_SEPARATOR.$item;
            if (is_dir($full)) {
                $this->deleteDirectory($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($path);
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
