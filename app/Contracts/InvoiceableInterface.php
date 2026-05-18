<?php

namespace App\Contracts;

use Corecave\Zatca\Models\ZatcaInvoice;

/**
 * Interface for entities that can be converted to ZATCA invoices.
 *
 * Implements Interface Segregation Principle (ISP) - only invoice-specific methods.
 */
interface InvoiceableInterface
{
    /**
     * Get the invoice number for this entity.
     * Format: {PREFIX}-{ID} (e.g., ORD-123, BKG-456)
     */
    public function getInvoiceNumber(): string;

    /**
     * Get line items for the invoice.
     * Each item should contain: name, quantity, unit_price, vat_category, vat_rate
     */
    public function getInvoiceLineItems(): array;

    /**
     * Get the total amount (excluding VAT).
     */
    public function getInvoiceSubtotal(): float;

    /**
     * Get the total VAT amount.
     */
    public function getInvoiceVatAmount(): float;

    /**
     * Get the total amount (including VAT).
     */
    public function getInvoiceTotal(): float;

    /**
     * Get buyer information for the invoice.
     * Returns null for anonymous B2C transactions.
     * For B2B, should include: name, vat_number, address, etc.
     */
    public function getBuyerInfo(): ?array;

    /**
     * Determine if this is a B2B transaction (requires standard invoice).
     * B2B = Standard invoice (clearance required)
     * B2C = Simplified invoice (reporting only)
     */
    public function isB2BTransaction(): bool;

    /**
     * Get the payment method used.
     * Returns ZATCA PaymentMethod enum value.
     */
    public function getPaymentMethod(): string;

    /**
     * Get the supply/service date.
     */
    public function getSupplyDate(): ?\Carbon\Carbon;

    /**
     * Get the related ZATCA invoice.
     */
    public function zatcaInvoice(): mixed;

    /**
     * Update ZATCA status and QR code after invoice processing.
     */
    public function updateZatcaStatus(string $status, ?string $qrCode = null, ?int $zatcaInvoiceId = null): void;
}
