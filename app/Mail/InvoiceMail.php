<?php

namespace App\Mail;

use Corecave\Zatca\Models\ZatcaInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification with ZATCA invoice attachment.
 *
 * Sends invoice details and attaches the signed XML and/or PDF.
 */
class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ZatcaInvoice $invoice,
        public string $customerName,
        public string $entityType = 'booking',
        public ?string $pdfContent = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->invoice->subtype === 'credit_note'
            ? __('Your Credit Note - :number', ['number' => $this->invoice->invoice_number])
            : __('Your Invoice - :number', ['number' => $this->invoice->invoice_number]);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'customerName' => $this->customerName,
                'entityType' => $this->entityType,
                'isCreditNote' => $this->invoice->subtype === 'credit_note',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Attach signed XML if available
        if (!empty($this->invoice->signed_xml)) {
            $attachments[] = Attachment::fromData(
                fn() => $this->invoice->signed_xml,
                $this->invoice->invoice_number . '.xml'
            )->withMime('application/xml');
        }

        // Attach PDF if provided
        if (!empty($this->pdfContent)) {
            $attachments[] = Attachment::fromData(
                fn() => $this->pdfContent,
                $this->invoice->invoice_number . '.pdf'
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
