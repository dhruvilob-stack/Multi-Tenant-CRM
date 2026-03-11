<?php

namespace App\Mail;

use App\Models\OrganizationSubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrganizationSubscriptionInvoice $invoice)
    {
    }

    public function build(): self
    {
        $subject = 'Subscription Invoice ' . ($this->invoice->invoice_number ?: 'Invoice');
        $mail = $this->subject($subject)
            ->view('mail.subscription-invoice', [
                'invoice' => $this->invoice,
                'organization' => $this->invoice->organization,
                'subscription' => $this->invoice->subscription,
            ]);

        if ($this->invoice->pdf_path && is_file($this->invoice->pdf_path)) {
            $mail->attach($this->invoice->pdf_path, [
                'as' => ($this->invoice->invoice_number ?: 'subscription-invoice') . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
