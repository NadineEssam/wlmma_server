<?php

namespace App\Services;

use App\Contracts\InvoiceableInterface;
use App\Enums\ZatcaStatus;
use App\Models\Booking;
use App\Models\Order;
use Corecave\Zatca\Contracts\InvoiceInterface;
use Corecave\Zatca\Enums\PaymentMethod;
use Corecave\Zatca\Facades\Zatca;
use Corecave\Zatca\Invoice\InvoiceBuilder;
use Corecave\Zatca\Models\ZatcaInvoice;
use Corecave\Zatca\Results\ProcessResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bridge service between application entities and ZATCA package.
 *
 * Implements Single Responsibility Principle (SRP) - handles invoice creation and submission.
 * Implements Dependency Inversion Principle (DIP) - depends on abstractions (InvoiceableInterface).
 */
class InvoiceService
{
    protected ?InvoiceEmailService $emailService = null;

    /**
     * Enable email notifications after invoice processing.
     */
    public function withEmailNotifications(): self
    {
        $this->emailService = app(InvoiceEmailService::class);
        return $this;
    }
    /**
     * Create and submit ZATCA invoice from an Order.
     */
    public function createFromOrder(Order $order): ?ProcessResult
    {
        return $this->processInvoice($order);
    }

    /**
     * Create and submit ZATCA invoice from a Booking.
     */
    public function createFromBooking(Booking $booking): ?ProcessResult
    {
        return $this->processInvoice($booking);
    }

    /**
     * Process invoice for any invoiceable entity.
     */
    public function processInvoice(InvoiceableInterface $entity): ?ProcessResult
    {
        try {
            // Mark as pending
            $entity->updateZatcaStatus(ZatcaStatus::PENDING->value);

            // Build the invoice
            $invoice = $this->buildInvoice($entity);

            // Submit to ZATCA
            $result = Zatca::process($invoice);

            // Store the result
            $this->storeResult($entity, $invoice, $result);

            Log::info('ZATCA invoice processed successfully', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'invoice_number' => $entity->getInvoiceNumber(),
                'status' => $result->getProcessType(),
            ]);

            // Send email notification if enabled and invoice was successful
            if ($this->emailService && $result->getResult()->isSuccess()) {
                $entityType = $entity instanceof Booking ? 'booking' : 'order';
                $this->emailService->sendInvoiceEmail($entity, $entityType);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('ZATCA invoice processing failed', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed
            $entity->updateZatcaStatus(ZatcaStatus::FAILED->value);

            return null;
        }
    }

    /**
     * Build ZATCA invoice from an invoiceable entity.
     */
    public function buildInvoice(InvoiceableInterface $entity): InvoiceInterface
    {
        // Determine invoice type based on B2B/B2C
        $builder = $entity->isB2BTransaction()
            ? InvoiceBuilder::standard()
            : InvoiceBuilder::simplified();

        // Set invoice number
        $builder->setInvoiceNumber($entity->getInvoiceNumber());

        // Set issue date
        $builder->setIssueDate(now());

        // Set supply date if available
        if ($supplyDate = $entity->getSupplyDate()) {
            $builder->setSupplyDate($supplyDate);
        }

        // Set payment method
        $paymentMethod = $this->mapPaymentMethod($entity->getPaymentMethod());
        $builder->setPaymentMethod($paymentMethod);

        // Set buyer info for B2B transactions
        if ($entity->isB2BTransaction() && $buyerInfo = $entity->getBuyerInfo()) {
            $builder->setBuyer($buyerInfo);
        }

        // Add line items
        foreach ($entity->getInvoiceLineItems() as $item) {
            $builder->addLineItem($item);
        }

        return $builder->build();
    }

    /**
     * Store the processing result back to the entity and database.
     */
    protected function storeResult(
        InvoiceableInterface $entity,
        InvoiceInterface $invoice,
        ProcessResult $result
    ): void {
        DB::transaction(function () use ($entity, $invoice, $result) {
            // Get the underlying result (ReportResult or ClearanceResult)
            $underlyingResult = $result->getResult();

            // Determine status
            $status = $underlyingResult->isSuccess()
                ? ($result->getProcessType() === 'reported' ? ZatcaStatus::REPORTED : ZatcaStatus::CLEARED)
                : ZatcaStatus::REJECTED;

            // Get QR code
            $qrCode = $underlyingResult->getQrCode();

            // Store in zatca_invoices table
            $zatcaInvoice = ZatcaInvoice::create([
                'uuid' => $invoice->getUuid(),
                'icv' => $invoice->getIcv(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'type' => $invoice->isSimplified() ? 'simplified' : 'standard',
                'subtype' => $invoice->getSubType()->value,
                'hash' => $invoice->getHash() ?? '',
                'previous_hash' => $invoice->getPreviousInvoiceHash(),
                'status' => $status->value,
                'signed_xml' => $underlyingResult->getSignedXml(),
                'qr_code' => $qrCode,
                'zatca_response' => $underlyingResult->getResponse(),
                'total_amount' => $entity->getInvoiceTotal(),
                'vat_amount' => $entity->getInvoiceVatAmount(),
                'invoiceable_type' => get_class($entity),
                'invoiceable_id' => $entity->id,
            ]);

            // Update entity with invoice info
            $entity->update([
                'zatca_invoice_id' => $zatcaInvoice->id,
                'zatca_status' => $status->value,
                'zatca_qr_code' => $qrCode,
                'invoice_number' => $invoice->getInvoiceNumber(),
            ]);
        });
    }

    /**
     * Map HyperPay payment method to ZATCA payment method.
     */
    protected function mapPaymentMethod(string $method): PaymentMethod
    {
        return match (strtolower($method)) {
            'visa', 'master', 'mada' => PaymentMethod::BANK_CARD,
            'cash' => PaymentMethod::CASH,
            'bank_transfer', 'transfer' => PaymentMethod::BANK_TRANSFER,
            'wallet' => PaymentMethod::DIRECT_DEBIT,
            default => PaymentMethod::BANK_CARD,
        };
    }

    /**
     * Retry failed invoice submission.
     */
    public function retryFailed(InvoiceableInterface $entity): ?ProcessResult
    {
        if ($entity->zatca_status !== ZatcaStatus::FAILED->value) {
            Log::warning('Attempted to retry non-failed invoice', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'current_status' => $entity->zatca_status,
            ]);
            return null;
        }

        return $this->processInvoice($entity);
    }

    /**
     * Generate invoice for preview (without submitting to ZATCA).
     */
    public function preview(InvoiceableInterface $entity): array
    {
        $invoice = $this->buildInvoice($entity);

        return [
            'invoice_number' => $invoice->getInvoiceNumber(),
            'uuid' => $invoice->getUuid(),
            'type' => $entity->isB2BTransaction() ? 'standard' : 'simplified',
            'issue_date' => $invoice->getIssueDate()->format('Y-m-d H:i:s'),
            'supply_date' => $invoice->getSupplyDate()?->format('Y-m-d H:i:s'),
            'seller' => $invoice->getSeller(),
            'buyer' => $invoice->getBuyer(),
            'line_items' => array_map(fn($item) => $item->toArray(), $invoice->getLineItems()),
            'subtotal' => $entity->getInvoiceSubtotal(),
            'vat_amount' => $entity->getInvoiceVatAmount(),
            'total' => $entity->getInvoiceTotal(),
            'payment_method' => $invoice->getPaymentMethod()?->value,
        ];
    }
}
