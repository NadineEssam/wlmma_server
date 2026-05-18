<?php

namespace App\Services;

use App\Contracts\InvoiceableInterface;
use App\Mail\InvoiceMail;
use App\Models\Booking;
use App\Models\Order;
use Corecave\Zatca\Models\ZatcaInvoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service for sending invoice email notifications to customers.
 */
class InvoiceEmailService
{
    /**
     * Send invoice email for a booking.
     */
    public function sendForBooking(Booking $booking): bool
    {
        return $this->sendInvoiceEmail($booking, 'booking');
    }

    /**
     * Send invoice email for an order.
     */
    public function sendForOrder(Order $order): bool
    {
        return $this->sendInvoiceEmail($order, 'order');
    }

    /**
     * Send invoice email for any invoiceable entity.
     */
    public function sendInvoiceEmail(InvoiceableInterface $entity, string $entityType): bool
    {
        try {
            // Get the ZATCA invoice
            $invoice = $this->getZatcaInvoice($entity);

            if (!$invoice) {
                Log::warning('InvoiceEmailService: No ZATCA invoice found', [
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                ]);
                return false;
            }

            // Get customer email
            $customerEmail = $this->getCustomerEmail($entity);
            $customerName = $this->getCustomerName($entity);

            if (!$customerEmail) {
                Log::warning('InvoiceEmailService: No customer email found', [
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                ]);
                return false;
            }

            // Send the email
            // Mail::to($customerEmail)->send(new InvoiceMail(
            //     invoice: $invoice,
            //     customerName: $customerName,
            //     entityType: $entityType,
            // ));

            sendViewEmail($customerEmail, __('ZATCA Invoice'), 'emails.invoice', [
                'invoice' => $invoice,
                'customerName' => $customerName,
                'entityType' => $entityType,
            ]);

            Log::info('InvoiceEmailService: Invoice email sent successfully', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_email' => $customerEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('InvoiceEmailService: Failed to send invoice email', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send credit note email.
     */
    public function sendCreditNoteEmail(InvoiceableInterface $entity, ZatcaInvoice $creditNote): bool
    {
        try {
            $customerEmail = $this->getCustomerEmail($entity);
            $customerName = $this->getCustomerName($entity);

            if (!$customerEmail) {
                Log::warning('InvoiceEmailService: No customer email for credit note', [
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                ]);
                return false;
            }

            $entityType = $entity instanceof Booking ? 'booking refund' : 'order refund';

            // Mail::to($customerEmail)->send(new InvoiceMail(
            //     invoice: $creditNote,
            //     customerName: $customerName,
            //     entityType: $entityType,
            // ));

            sendViewEmail($customerEmail, __('Credit Note'), 'emails.invoice', [
                'invoice' => $creditNote,
                'customerName' => $customerName,
                'entityType' => $entityType,
            ]);

            Log::info('InvoiceEmailService: Credit note email sent successfully', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'credit_note_number' => $creditNote->invoice_number,
                'customer_email' => $customerEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('InvoiceEmailService: Failed to send credit note email', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the ZATCA invoice for an entity.
     */
    protected function getZatcaInvoice(InvoiceableInterface $entity): ?ZatcaInvoice
    {
        if (!empty($entity->zatca_invoice_id)) {
            return ZatcaInvoice::find($entity->zatca_invoice_id);
        }

        // Try to find by polymorphic relationship
        return ZatcaInvoice::where('invoiceable_type', get_class($entity))
            ->where('invoiceable_id', $entity->id)
            ->where('subtype', 'invoice')
            ->latest()
            ->first();
    }

    /**
     * Get customer email from entity.
     */
    protected function getCustomerEmail(InvoiceableInterface $entity): ?string
    {
        if ($entity instanceof Booking) {
            return $entity->customer?->email ?? $entity->customer?->user?->email;
        }

        if ($entity instanceof Order) {
            return $entity->user?->email;
        }

        // Try generic approach
        if (method_exists($entity, 'customer') && $entity->customer) {
            return $entity->customer->email ?? null;
        }

        if (method_exists($entity, 'user') && $entity->user) {
            return $entity->user->email ?? null;
        }

        return null;
    }

    /**
     * Get customer name from entity.
     */
    protected function getCustomerName(InvoiceableInterface $entity): string
    {
        if ($entity instanceof Booking) {
            return $entity->customer?->name
                ?? $entity->customer?->user?->name
                ?? 'Valued Customer';
        }

        if ($entity instanceof Order) {
            return $entity->user?->name ?? 'Valued Customer';
        }

        // Try generic approach
        if (method_exists($entity, 'customer') && $entity->customer) {
            return $entity->customer->name ?? 'Valued Customer';
        }

        if (method_exists($entity, 'user') && $entity->user) {
            return $entity->user->name ?? 'Valued Customer';
        }

        return 'Valued Customer';
    }
}
