<?php

namespace App\Traits;

use App\Enums\ZatcaStatus;
use Corecave\Zatca\Models\ZatcaInvoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that can be invoiced via ZATCA.
 *
 * Implements DRY principle - shared ZATCA functionality for Order and Booking.
 */
trait Invoiceable
{
    /**
     * Get the related ZATCA invoice.
     */
    public function zatcaInvoice(): BelongsTo
    {
        return $this->belongsTo(ZatcaInvoice::class, 'zatca_invoice_id');
    }

    /**
     * Update ZATCA status and QR code after invoice processing.
     */
    public function updateZatcaStatus(string $status, ?string $qrCode = null, ?int $zatcaInvoiceId = null): void
    {
        $data = ['zatca_status' => $status];

        if ($qrCode !== null) {
            $data['zatca_qr_code'] = $qrCode;
        }

        if ($zatcaInvoiceId !== null) {
            $data['zatca_invoice_id'] = $zatcaInvoiceId;
        }

        $this->update($data);
    }

    /**
     * Check if ZATCA invoice was generated.
     */
    public function hasZatcaInvoice(): bool
    {
        return $this->zatca_invoice_id !== null;
    }

    /**
     * Check if ZATCA invoice was successfully submitted.
     */
    public function isZatcaSuccess(): bool
    {
        if ($this->zatca_status === null) {
            return false;
        }

        $status = ZatcaStatus::tryFrom($this->zatca_status);
        return $status?->isSuccess() ?? false;
    }

    /**
     * Get ZATCA status as enum.
     */
    public function getZatcaStatusEnum(): ?ZatcaStatus
    {
        return $this->zatca_status ? ZatcaStatus::tryFrom($this->zatca_status) : null;
    }

    /**
     * Scope for entities with successful ZATCA invoices.
     */
    public function scopeZatcaSuccess($query)
    {
        return $query->whereIn('zatca_status', [
            ZatcaStatus::REPORTED->value,
            ZatcaStatus::CLEARED->value,
        ]);
    }

    /**
     * Scope for entities with failed ZATCA invoices.
     */
    public function scopeZatcaFailed($query)
    {
        return $query->where('zatca_status', ZatcaStatus::FAILED->value);
    }

    /**
     * Scope for entities pending ZATCA invoice.
     */
    public function scopeZatcaPending($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('zatca_status')
                ->orWhere('zatca_status', ZatcaStatus::PENDING->value);
        });
    }

    /**
     * Get the default VAT rate (15% for Saudi Arabia).
     */
    protected function getDefaultVatRate(): float
    {
        return config('zatca.invoice.vat_rate', 15.00);
    }

    /**
     * Calculate VAT amount from a subtotal.
     */
    protected function calculateVat(float $subtotal): float
    {
        return round($subtotal * ($this->getDefaultVatRate() / 100), 2);
    }
}
