<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Http\Controllers\Controller;
use App\Services\InvoicePdfService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoicePdfController extends Controller
{
    public function download(int $id, InvoicePdfService $service): BinaryFileResponse
    {
        $invoice = InvoiceResource::getEloquentQuery()->whereKey($id)->firstOrFail();

        $generated = $service->generate($invoice);

        return response()->download(
            $generated['path'],
            $generated['filename'],
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$generated['filename'].'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        )->deleteFileAfterSend(true);
    }
}
