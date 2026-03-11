<?php

namespace App\Services;

use App\Models\OrganizationSubscriptionInvoice;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class SubscriptionInvoicePdfService
{
    /**
     * @return array{path: string, filename: string}
     */
    public function generate(OrganizationSubscriptionInvoice $invoice): array
    {
        $outputDir = storage_path('app/tmp/subscription-invoices');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $fileBase = sprintf('subscription-invoice-%s-%s', $invoice->id, Str::random(8));
        $htmlPath = $outputDir.'/'.$fileBase.'.html';
        $pdfPath = $outputDir.'/'.$fileBase.'.pdf';

        file_put_contents($htmlPath, view('pdf.subscription-invoice', [
            'invoice' => $invoice,
        ])->render());

        $errors = [];

        if (! $this->tryGenerateWithHeadlessBrowser($htmlPath, $pdfPath, $errors)
            && ! $this->tryGenerateWithLibreOffice($htmlPath, $pdfPath, $outputDir, $errors)) {
            @unlink($htmlPath);
            $details = $errors === [] ? 'No PDF engine available.' : implode(' | ', $errors);

            throw new RuntimeException('Unable to generate subscription invoice PDF. '.$details);
        }

        @unlink($htmlPath);

        return [
            'path' => $pdfPath,
            'filename' => ($invoice->invoice_number ?: 'subscription-invoice').'.pdf',
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
}
