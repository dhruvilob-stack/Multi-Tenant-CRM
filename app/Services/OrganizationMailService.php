<?php

namespace App\Services;

use App\Filament\Pages\InboxMail;
use App\Mail\OrganizationMailMessage;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrganizationMail;
use App\Models\OrganizationMailRecipient;
use App\Models\User;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationMailService
{
    /**
     * @param array{from_email?:string,to:array<int,mixed>,cc?:array<int,mixed>,bcc?:array<int,mixed>,subject:string,body:string,template_key?:string|null,order_id?:int|null,invoice_id?:int|null,attach_invoice_pdf?:bool,attach_sales_report?:bool,media_attachments?:array<int,string>} $data
     */
    public function send(User $sender, array $data): OrganizationMail
    {
        $organization = $sender->organization;

        if (! $organization) {
            throw ValidationException::withMessages(['organization' => 'Sender organization not found.']);
        }

        $to = $this->normalizeRecipientEmails($organization->id, $data['to'] ?? []);
        $cc = $this->normalizeRecipientEmails($organization->id, $data['cc'] ?? []);
        $bcc = $this->normalizeRecipientEmails($organization->id, $data['bcc'] ?? []);

        if ($to === []) {
            throw ValidationException::withMessages(['to' => 'At least one recipient is required.']);
        }

        $mail = OrganizationMail::query()->create([
            'organization_id' => $organization->id,
            'sender_id' => $sender->id,
            'sender_email' => $data['from_email'] ?? $sender->email,
            'subject' => (string) $data['subject'],
            'body' => (string) $data['body'],
            'template_key' => $data['template_key'] ?? null,
            'meta' => [
                'order_id' => $data['order_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'attach_invoice_pdf' => (bool) ($data['attach_invoice_pdf'] ?? false),
                'attach_sales_report' => (bool) ($data['attach_sales_report'] ?? false),
                'media_attachments' => array_values(array_filter((array) ($data['media_attachments'] ?? []))),
            ],
            'sent_at' => now(),
        ]);

        $attachments = $this->prepareAttachments($mail);
        $meta = (array) ($mail->meta ?? []);
        $meta['attachments'] = $attachments;
        $mail->forceFill(['meta' => $meta])->saveQuietly();

        foreach ([['to', $to], ['cc', $cc], ['bcc', $bcc]] as [$type, $emails]) {
            foreach ($emails as $email) {
                $recipient = User::query()
                    ->where('organization_id', $organization->id)
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->first();

                OrganizationMailRecipient::query()->create([
                    'mail_id' => $mail->id,
                    'recipient_id' => $recipient?->id,
                    'recipient_email' => $email,
                    'recipient_type' => $type,
                ]);

                if ($recipient) {
                    FilamentNotification::make()
                        ->title('New mail: '.$mail->subject)
                        ->body('From: '.$mail->sender_email)
                        ->actions([
                            Action::make('open')
                                ->label('Open Inbox')
                                ->url(InboxMail::getUrl()),
                        ])
                        ->sendToDatabase($recipient, isEventDispatched: true)
                        ->broadcast($recipient);
                }
            }
        }

        Mail::to($to)
            ->cc($cc)
            ->bcc($bcc)
            ->send(new OrganizationMailMessage($mail, $attachments));

        return $mail;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeRecipientEmails(int $organizationId, array $input): array
    {
        $emails = collect($input)
            ->map(fn ($value) => mb_strtolower(trim((string) $value)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return [];
        }

        return $emails;
    }

    /**
     * @return array<int, array{path:string,name:string,mime:string,size:int}>
     */
    protected function prepareAttachments(OrganizationMail $mail): array
    {
        $meta = (array) ($mail->meta ?? []);
        $attachments = [];

        if ((bool) Arr::get($meta, 'attach_invoice_pdf', false)) {
            $invoiceId = (int) Arr::get($meta, 'invoice_id', 0);
            if ($invoiceId > 0) {
                $invoice = Invoice::query()->find($invoiceId);
                if ($invoice) {
                    $pdf = app(InvoicePdfService::class)->generate($invoice);
                    $attachments[] = $this->persistAttachment(
                        $pdf['path'],
                        (string) ($pdf['filename'] ?? ('invoice-'.$invoice->id.'.pdf')),
                        'application/pdf'
                    );
                }
            }
        }

        if ((bool) Arr::get($meta, 'attach_sales_report', false)) {
            $organization = $mail->organization;
            if ($organization) {
                $report = app(DashboardReportPdfService::class)->generateForOrganization($organization);
                $attachments[] = $this->persistAttachment(
                    $report['path'],
                    (string) ($report['filename'] ?? ('sales-report-'.$organization->id.'.pdf')),
                    'application/pdf'
                );
            }
        }

        foreach ((array) Arr::get($meta, 'media_attachments', []) as $path) {
            if (is_string($path) && is_file($path)) {
                $attachments[] = $this->persistAttachment($path, basename($path), File::mimeType($path) ?: 'application/octet-stream');
            }
        }

        return array_values(array_filter($attachments, fn (array $f): bool => isset($f['path']) && is_file($f['path'])));
    }

    /**
     * @return array{path:string,name:string,mime:string,size:int}
     */
    protected function persistAttachment(string $sourcePath, string $filename, string $mime): array
    {
        $safeName = trim($filename) !== '' ? $filename : basename($sourcePath);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $safeName) ?: 'attachment.bin';
        $storedRelative = 'mail-attachments/'.Str::uuid()->toString().'-'.$safeName;

        $content = file_get_contents($sourcePath);
        if ($content === false) {
            throw ValidationException::withMessages(['attachment' => 'Unable to read attachment file: '.$safeName]);
        }

        Storage::disk('local')->put($storedRelative, $content);
        $storedPath = Storage::disk('local')->path($storedRelative);

        return [
            'path' => $storedPath,
            'name' => $safeName,
            'mime' => $mime,
            'size' => (int) @filesize($storedPath),
        ];
    }

    public function sendOrderStatusUpdate(Order $order, string $status): void
    {
        $order->loadMissing('consumer', 'vendor.organization', 'invoice');

        $consumer = $order->consumer;
        $sender = $order->vendor;

        if (! $consumer || ! $sender) {
            return;
        }

        $subject = "Order {$order->order_number} status update: ".strtoupper($status);
        $body = sprintf(
            '<p>Dear %s,</p><p>Your order <strong>%s</strong> is now <strong>%s</strong>.</p><p>Thank you.</p>',
            e($consumer->name),
            e($order->order_number),
            e($status)
        );

        $this->send($sender, [
            'from_email' => $sender->email,
            'to' => [$consumer->email],
            'cc' => [],
            'bcc' => [],
            'subject' => $subject,
            'body' => $body,
            'template_key' => 'order_status_update',
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'attach_invoice_pdf' => (bool) $order->invoice_id,
            'attach_sales_report' => false,
        ]);
    }

    public function sendInvoiceAndRevenueUpdate(Order $order): void
    {
        $order->loadMissing('consumer', 'vendor.organization', 'invoice');
        $sender = $order->vendor;
        $consumer = $order->consumer;
        $invoice = $order->invoice;

        if (! $sender || ! $consumer || ! $invoice) {
            return;
        }

        $this->send($sender, [
            'from_email' => $sender->email,
            'to' => [$consumer->email],
            'cc' => [],
            'bcc' => [],
            'subject' => "Invoice {$invoice->invoice_number} for order {$order->order_number}",
            'body' => '<p>Hello '.e((string) $consumer->name).',</p><p>Your order has been processed. Please find your invoice attached.</p>',
            'template_key' => 'invoice_ready',
            'order_id' => $order->id,
            'invoice_id' => $invoice->id,
            'attach_invoice_pdf' => true,
            'attach_sales_report' => false,
        ]);

        $orgAdminEmails = User::query()
            ->where('organization_id', $sender->organization_id)
            ->whereIn('role', [UserRole::ORG_ADMIN])
            ->pluck('email')
            ->filter(fn ($email): bool => is_string($email) && $email !== '')
            ->values()
            ->all();

        if ($orgAdminEmails === []) {
            return;
        }

        $this->send($sender, [
            'from_email' => $sender->email,
            'to' => $orgAdminEmails,
            'cc' => [],
            'bcc' => [],
            'subject' => "Revenue update: {$invoice->invoice_number}",
            'body' => '<p>Revenue generated from order <strong>'.e((string) $order->order_number).'</strong>.</p><p>Total: <strong>$'.number_format((float) $invoice->grand_total, 2).'</strong></p>',
            'template_key' => 'sales_report',
            'order_id' => $order->id,
            'invoice_id' => $invoice->id,
            'attach_invoice_pdf' => true,
            'attach_sales_report' => true,
        ]);
    }

    public function sendDeliveredInvoiceToCustomer(Order $order, Invoice $invoice): void
    {
        $order->loadMissing('consumer', 'vendor.organization', 'items.product');
        $sender = $order->vendor;
        $consumer = $order->consumer;

        if (! $sender || ! $consumer) {
            return;
        }

        $rows = $order->items
            ->map(function ($item): string {
                $name = e((string) $item->item_name);
                $qty = number_format((float) $item->qty, 3);
                $unit = number_format((float) $item->unit_price, 2);
                $total = number_format((float) $item->line_total, 2);

                return "<tr><td style='padding:6px;border:1px solid #e5e7eb'>{$name}</td><td style='padding:6px;border:1px solid #e5e7eb'>{$qty}</td><td style='padding:6px;border:1px solid #e5e7eb'>{$unit}</td><td style='padding:6px;border:1px solid #e5e7eb'>{$total}</td></tr>";
            })
            ->implode('');

        $body = '<p>Hello '.e((string) $consumer->name).',</p>'
            .'<p>Your order <strong>'.e((string) $order->order_number).'</strong> has been delivered successfully.</p>'
            .'<p>Invoice <strong>'.e((string) $invoice->invoice_number).'</strong> is attached as PDF.</p>'
            .'<div style="margin:12px 0"><strong>Payment Method:</strong> '.e((string) $order->payment_method).'<br>'
            .'<strong>Payment Reference:</strong> '.e((string) ($order->payment_reference_number ?: '-')).'<br>'
            .'<strong>Payment Status:</strong> '.e((string) $order->payment_status).'<br>'
            .'<strong>Total Amount:</strong> '.number_format((float) ($order->total_amount_billed ?? $order->total_amount ?? 0), 2).' '.e((string) ($order->currency ?: 'USD')).'</div>'
            .'<table style="width:100%;border-collapse:collapse"><thead><tr><th style="text-align:left;padding:6px;border:1px solid #e5e7eb">Product</th><th style="text-align:left;padding:6px;border:1px solid #e5e7eb">Qty</th><th style="text-align:left;padding:6px;border:1px solid #e5e7eb">Unit Price</th><th style="text-align:left;padding:6px;border:1px solid #e5e7eb">Line Total</th></tr></thead><tbody>'.$rows.'</tbody></table>'
            .'<p style="margin-top:12px">Thank you for shopping with us.</p>';

        $this->send($sender, [
            'from_email' => $sender->email,
            'to' => [$consumer->email],
            'cc' => [],
            'bcc' => [],
            'subject' => "Order {$order->order_number} delivered - Invoice {$invoice->invoice_number}",
            'body' => $body,
            'template_key' => 'delivered_invoice',
            'order_id' => $order->id,
            'invoice_id' => $invoice->id,
            'attach_invoice_pdf' => true,
            'attach_sales_report' => false,
        ]);
    }
}
