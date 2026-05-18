<?php

namespace App\Models;

use App\Contracts\InvoiceableInterface;
use App\Traits\Invoiceable;
use Carbon\Carbon;
use Corecave\Zatca\Enums\VatCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model implements InvoiceableInterface
{
    use HasFactory, Invoiceable;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'old_price',
        'total',
        'status',
        'is_paid',
        'is_returned',
        'additional_details',
        'lat',
        'long',
        'location',
        'zatca_invoice_id',
        'zatca_status',
        'zatca_qr_code',
        'invoice_number',
        'shortNationalAddress',
        'province',
        'cityGovernorate',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_returned' => 'boolean',
    ];

    /**
     * Define relationship with the User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define relationship with the OrderItem model
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ==========================================
    // InvoiceableInterface Implementation
    // ==========================================

    /**
     * Get the invoice number for this order.
     */
    public function getInvoiceNumber(): string
    {
        return $this->invoice_number ?? 'ORD-' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Get line items for the invoice.
     *
     * All prices in the system are VAT-inclusive. This method extracts
     * VAT-exclusive unit prices for ZATCA compliance.
     */
    public function getInvoiceLineItems(): array
    {
        $vatRate = $this->getDefaultVatRate();
        $vatMultiplier = 1 + ($vatRate / 100);

        return $this->orderItems->map(function ($item, $index) use ($vatRate, $vatMultiplier) {
            $tool = $item->tool;
            // item->price is VAT-inclusive, extract VAT-exclusive price
            $vatExclusivePrice = round((float) $item->price / $vatMultiplier, 2);

            return [
                'id' => $index + 1,
                'name' => $tool->name_en ?? $tool->name ?? 'Product',
                'quantity' => (float) $item->quantity,
                'unit_price' => $vatExclusivePrice,
                'vat_category' => VatCategory::STANDARD,
                'vat_rate' => $vatRate,
                'unit_code' => 'PCE', // Piece
            ];
        })->toArray();
    }

    /**
     * Get the subtotal (excluding VAT).
     *
     * The total is VAT-inclusive, so we need to extract the VAT-exclusive subtotal.
     * Formula: subtotal = total / (1 + VAT_RATE/100)
     * Example: 100 SAR total = 86.96 SAR subtotal + 13.04 SAR VAT
     */
    public function getInvoiceSubtotal(): float
    {
        $vatRate = $this->getDefaultVatRate();
        $vatMultiplier = 1 + ($vatRate / 100);

        return round((float) $this->total / $vatMultiplier, 2);
    }

    /**
     * Get the VAT amount.
     *
     * Calculated from the VAT-inclusive total price.
     * Formula: vat = total - subtotal
     */
    public function getInvoiceVatAmount(): float
    {
        return round((float) $this->total - $this->getInvoiceSubtotal(), 2);
    }

    /**
     * Get the total amount (including VAT).
     *
     * This is the original total since it's already VAT-inclusive.
     */
    public function getInvoiceTotal(): float
    {
        return (float) $this->total;
    }

    /**
     * Get buyer information.
     */
    public function getBuyerInfo(): ?array
    {
        $user = $this->user;

        if (!$user) {
            return null;
        }

        // For B2C, buyer info is optional
        // For B2B, we'd need VAT number and full address
        return [
            'name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
            'address' => [
                'street' => $user->street ?? '',
                'city' => $user->city ?? '',
                'postal_code' => $user->postcode ?? '',
                'country' => $user->country ?? 'SA',
            ],
        ];
    }

    /**
     * Determine if this is a B2B transaction.
     * Commercial tool orders are typically B2C unless buyer has VAT.
     */
    public function isB2BTransaction(): bool
    {
        // Check if buyer has VAT number (indicating B2B)
        $user = $this->user;

        if ($user && !empty($user->vat_number)) {
            return true;
        }

        return false;
    }

    /**
     * Get the payment method used.
     */
    public function getPaymentMethod(): string
    {
        // Orders are typically paid via HyperPay (card)
        return 'bank_card';
    }

    /**
     * Get the supply date.
     */
    public function getSupplyDate(): ?Carbon
    {
        return $this->created_at;
    }
}
