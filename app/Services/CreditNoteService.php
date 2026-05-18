<?php

namespace App\Services;

use App\Contracts\InvoiceableInterface;
use App\Enums\ZatcaStatus;
use App\Models\Booking;
use App\Models\Order;
use Corecave\Zatca\Contracts\InvoiceInterface;
use Corecave\Zatca\Enums\VatCategory;
use Corecave\Zatca\Facades\Zatca;
use Corecave\Zatca\Invoice\InvoiceBuilder;
use Corecave\Zatca\Models\ZatcaInvoice;
use Corecave\Zatca\Results\ProcessResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for creating and submitting ZATCA credit notes.
 *
 * Credit notes are issued when:
 * - A booking/order is refunded
 * - A partial refund is issued
 * - An invoice needs correction
 *
 * @implements Single Responsibility Principle (SRP)
 */
class CreditNoteService
{
    protected ?InvoiceEmailService $emailService = null;

    /**
     * Enable email notifications after credit note processing.
     */
    public function withEmailNotifications(): self
    {
        $this->emailService = app(InvoiceEmailService::class);
        return $this;
    }

    /**
     * Create and submit a credit note for a refunded booking.
     *
     * @param Booking $booking The refunded booking
     * @param float|null $refundAmount The refund amount (null = full refund)
     * @param string $reason The reason for the credit note
     * @return ProcessResult|null
     */
    public function createForBooking(
        Booking $booking,
        ?float $refundAmount = null,
        string $reason = 'Booking cancellation and refund'
    ): ?ProcessResult {
        // Verify original invoice exists
        if (!$this->hasValidOriginalInvoice($booking)) {
            Log::warning('Cannot create credit note: no original invoice found', [
                'booking_id' => $booking->id,
            ]);
            return null;
        }

        return $this->processCreditNote($booking, $refundAmount, $reason);
    }

    /**
     * Create and submit a credit note for a refunded order.
     *
     * @param Order $order The refunded order
     * @param float|null $refundAmount The refund amount (null = full refund)
     * @param string $reason The reason for the credit note
     * @return ProcessResult|null
     */
    public function createForOrder(
        Order $order,
        ?float $refundAmount = null,
        string $reason = 'Order cancellation and refund'
    ): ?ProcessResult {
        // Verify original invoice exists
        if (!$this->hasValidOriginalInvoice($order)) {
            Log::warning('Cannot create credit note: no original invoice found', [
                'order_id' => $order->id,
            ]);
            return null;
        }

        return $this->processCreditNote($order, $refundAmount, $reason);
    }

    /**
     * Process credit note for any invoiceable entity.
     */
    protected function processCreditNote(
        InvoiceableInterface $entity,
        ?float $refundAmount,
        string $reason
    ): ?ProcessResult {
        try {
            // Get the original invoice
            $originalInvoice = $this->getOriginalInvoice($entity);

            // Build the credit note
            $creditNote = $this->buildCreditNote($entity, $originalInvoice, $refundAmount, $reason);

            // Submit to ZATCA
            $result = Zatca::process($creditNote);

            // Store the result
            $storedCreditNote = $this->storeCreditNoteResult($entity, $creditNote, $result, $originalInvoice);

            // Log::info('ZATCA credit note processed successfully', [
            //     'entity_type' => get_class($entity),
            //     'entity_id' => $entity->id,
            //     'credit_note_number' => $creditNote->getInvoiceNumber(),
            //     'original_invoice' => $originalInvoice->invoice_number,
            //     'refund_amount' => $refundAmount ?? $entity->getInvoiceTotal(),
            //     'status' => $result->getType(),
            // ]);


Log::info('ZATCA credit note processed successfully', [
    'entity_type'       => get_class($entity),
    'entity_id'         => $entity->id,
    'credit_note_number'=> $creditNote->getInvoiceNumber(),
    'original_invoice'  => $originalInvoice->invoice_number,
    'refund_amount'     => $refundAmount ?? $entity->getInvoiceTotal(),
    'status'            => $result->wasReported() ? 'REPORTED' : ($result->wasCleared() ? 'CLEARED' : 'UNKNOWN'),
]);


            // Send email notification if enabled and credit note was successful
            if ($this->emailService && $result->getResult()->isSuccess()) {
                $this->emailService->sendCreditNoteEmail($entity, $storedCreditNote);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('ZATCA credit note processing failed', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Build a credit note invoice.
     */
    protected function buildCreditNote(
        InvoiceableInterface $entity,
        ZatcaInvoice $originalInvoice,
        ?float $refundAmount,
        string $reason
    ): InvoiceInterface {
        // Determine if simplified or standard based on original
        $isSimplified = $originalInvoice->type === 'simplified';

        $builder = InvoiceBuilder::creditNote($isSimplified);

        // Set credit note number (CN prefix)
        $creditNoteNumber = 'CN-' . $entity->getInvoiceNumber();
        $builder->setInvoiceNumber($creditNoteNumber);

        // Set issue date
        $builder->setIssueDate(now());

        // Reference to original invoice - REQUIRED for credit notes
        $builder->setOriginalInvoice($originalInvoice->invoice_number);

        // Set reason for credit note
        $builder->setReason($reason);

        // Set buyer info if B2B
        if (!$isSimplified && $buyerInfo = $entity->getBuyerInfo()) {
            $builder->setBuyer($buyerInfo);
        }

        // Determine refund percentage for line items
        $originalTotal = $entity->getInvoiceTotal();
        $actualRefund = $refundAmount ?? $originalTotal;
        $refundPercentage = $originalTotal > 0 ? ($actualRefund / $originalTotal) : 1;

        // Add line items (proportionally reduced if partial refund)
        foreach ($entity->getInvoiceLineItems() as $item) {
            $adjustedItem = $item;
            if ($refundPercentage < 1) {
                // For partial refunds, adjust quantities or amounts
                $adjustedItem['quantity'] = round($item['quantity'] * $refundPercentage, 2);
            }
            $builder->addLineItem($adjustedItem);
        }

        return $builder->build();
    }

    /**
     * Store the credit note result.
     */
    protected function storeCreditNoteResult(
        InvoiceableInterface $entity,
        InvoiceInterface $creditNote,
        ProcessResult $result,
        ZatcaInvoice $originalInvoice
    ): ZatcaInvoice {
        return DB::transaction(function () use ($entity, $creditNote, $result, $originalInvoice) {
            $underlyingResult = $result->getResult();

            // $status = $underlyingResult->isSuccess()
            //     ? ($result->getType() === 'reported' ? ZatcaStatus::REPORTED : ZatcaStatus::CLEARED)
            //     : ZatcaStatus::REJECTED;
            
            
            $reportingStatus = $underlyingResult->getResponse()['reportingStatus'] ?? null;

            $status = $underlyingResult->isSuccess()
                ? ($reportingStatus === 'REPORTED' ? ZatcaStatus::REPORTED : ZatcaStatus::CLEARED)
                : ZatcaStatus::REJECTED;

            // Store credit note in zatca_invoices table
            return ZatcaInvoice::create([
                'uuid' => $creditNote->getUuid(),
                'icv' => $creditNote->getIcv(),
                'invoice_number' => $creditNote->getInvoiceNumber(),
                'type' => $creditNote->isSimplified() ? 'simplified' : 'standard',
                'subtype' => $creditNote->getSubType()->value,
                'hash' => $creditNote->getHash() ?? '',
                'previous_hash' => $creditNote->getPreviousInvoiceHash(),
                'status' => $status->value,
                'signed_xml' => $underlyingResult->getSignedXml(),
                'qr_code' => $underlyingResult->getQrCode(),
                'zatca_response' => $underlyingResult->getResponse(),
                'total_amount' => $entity->getInvoiceTotal(),
                'vat_amount' => $entity->getInvoiceVatAmount(),
                'reference_id' => $originalInvoice->id,  // Reference to original invoice
                'invoiceable_type' => get_class($entity),
                'invoiceable_id' => $entity->id,
            ]);
        });
    }

    /**
     * Check if entity has a valid original invoice for credit note.
     */
    protected function hasValidOriginalInvoice(InvoiceableInterface $entity): bool
    {
        return !empty($entity->zatca_invoice_id) &&
            in_array($entity->zatca_status, [
                ZatcaStatus::REPORTED->value,
                ZatcaStatus::CLEARED->value,
            ]);
    }

    /**
     * Get the original ZATCA invoice for an entity.
     */
    protected function getOriginalInvoice(InvoiceableInterface $entity): ZatcaInvoice
    {
        return ZatcaInvoice::findOrFail($entity->zatca_invoice_id);
    }
}
