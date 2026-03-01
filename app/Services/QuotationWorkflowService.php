<?php

namespace App\Services;

use App\Events\InvoiceCreatedEvent;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\QuotationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationWorkflowService
{
    public function send(Quotation $quotation): Quotation
    {
        $this->ensureAllowed($quotation, [QuotationStatus::DRAFT, QuotationStatus::NEGOTIATED], QuotationStatus::SENT);

        $quotation->update(['status' => QuotationStatus::SENT]);

        return $quotation->refresh();
    }

    public function negotiate(Quotation $quotation): Quotation
    {
        $this->ensureAllowed($quotation, [QuotationStatus::SENT], QuotationStatus::NEGOTIATED);

        $quotation->update(['status' => QuotationStatus::NEGOTIATED]);

        return $quotation->refresh();
    }

    public function reject(Quotation $quotation): Quotation
    {
        $this->ensureAllowed($quotation, [QuotationStatus::SENT, QuotationStatus::NEGOTIATED], QuotationStatus::REJECTED);

        $quotation->update(['status' => QuotationStatus::REJECTED]);

        return $quotation->refresh();
    }

    public function confirm(Quotation $quotation, int $paymentTermsDays = 15): Invoice
    {
        $this->ensureAllowed($quotation, [QuotationStatus::SENT, QuotationStatus::NEGOTIATED], QuotationStatus::CONFIRMED);

        if ($quotation->invoice()->exists()) {
            return $quotation->invoice;
        }

        return DB::transaction(function () use ($quotation, $paymentTermsDays): Invoice {
            $quotation->update(['status' => QuotationStatus::CONFIRMED]);

            $invoice = $this->createInvoiceFromQuotation($quotation, $paymentTermsDays);

            $quotation->update(['status' => QuotationStatus::CONVERTED]);

            InvoiceCreatedEvent::dispatch($invoice);

            return $invoice;
        });
    }

    private function createInvoiceFromQuotation(Quotation $quotation, int $paymentTermsDays): Invoice
    {
        $invoice = Invoice::query()->create([
            'invoice_number' => $this->nextInvoiceNumber(),
            'quotation_id' => $quotation->id,
            'subject' => $quotation->subject ?: 'Sales Order',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays($paymentTermsDays)->toDateString(),
            'status' => 'auto_created',
            'terms_conditions' => $quotation->terms_conditions,
            'description' => $quotation->notes,
            'tax_amount' => $quotation->tax_amount,
            'pre_tax_total' => $quotation->subtotal,
            'overall_discount_value' => $quotation->discount_amount,
            'grand_total' => $quotation->grand_total,
            'balance' => $quotation->grand_total,
            'currency' => 'USD',
        ]);

        $quotation->items()->each(function (QuotationItem $item) use ($invoice): void {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'item_name' => $item->item_name,
                'qty' => $item->qty,
                'selling_price' => $item->selling_price,
                'discount_percent' => $item->discount_percent,
                'net_price' => $item->net_price,
                'total' => $item->total,
                'tax_rate' => $item->tax_rate,
                'tax_amount' => $item->tax_amount,
            ]);
        });

        return $invoice;
    }

    private function nextInvoiceNumber(): string
    {
        $lastId = (int) Invoice::query()->max('id') + 1;

        return sprintf('INV-%s-%04d', now()->format('Y'), $lastId);
    }

    private function ensureAllowed(Quotation $quotation, array $allowedFrom, string $target): void
    {
        if (! in_array($quotation->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Cannot move quotation from [%s] to [%s].',
                    $quotation->status,
                    $target
                ),
            ]);
        }
    }
}
