<?php

namespace App\Models;

use App\Contracts\InvoiceableInterface;
use App\Models\CommercialTool;
use App\Traits\Invoiceable;
use Carbon\Carbon;
use Corecave\Zatca\Enums\VatCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model implements InvoiceableInterface
{
    use HasFactory, Invoiceable;

    protected $fillable = [
        'activity_id',
        'is_returned',
        'tool_id',
        'tool_capacity',
        'total_price',
        'date',
        'time',
        'capacity',
        'photographer',
        'tour_guide',
        'phone_number',
        'user_name',
        'user_id',
        'status_id',
        'is_paied',
        'code',
        'zatca_invoice_id',
        'zatca_status',
        'zatca_qr_code',
        'invoice_number',
    ];

    protected $casts = [
        // 'date' => 'date',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function booking_status()
    {
        return $this->belongsTo(BookingStatus::class, 'status_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    

    // ==========================================
    // InvoiceableInterface Implementation
    // ==========================================

    /**
     * Get the invoice number for this booking.
     */
    public function getInvoiceNumber(): string
    {
        return $this->invoice_number ?? 'BKG-' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Get line items for the invoice.
     *
     * All prices in the system are VAT-inclusive. This method extracts
     * VAT-exclusive unit prices for ZATCA compliance.
     *
     * Line items include:
     * 1. Main activity booking (activity price * capacity)
     * 2. Photographer service (if enabled)
     * 3. Tour guide service (if enabled)
     * 4. Tool rentals (if any tools are booked)
     */
    public function getInvoiceLineItems(): array
    {
        $items = [];
        $activity = $this->activity;
        $lineId = 1;
        $vatRate = $this->getDefaultVatRate();
        $vatMultiplier = 1 + ($vatRate / 100);

        // Main activity booking - use activity price, not total_price
        // total_price includes services and tools, so we use the base activity price
        if ($activity) {
            $vatInclusiveUnitPrice = (float) $activity->price;
            $vatExclusiveUnitPrice = round($vatInclusiveUnitPrice / $vatMultiplier, 2);

            $items[] = [
                'id' => $lineId++,
                'name' => $activity->title_en ?? $activity->title_ar ?? 'Activity Booking',
                'quantity' => (float) $this->capacity,
                'unit_price' => $vatExclusiveUnitPrice,
                'vat_category' => VatCategory::STANDARD,
                'vat_rate' => $vatRate,
                'unit_code' => 'C62', // Unit (person)
            ];
        }

        // Add photographer service if included
        if ($this->photographer && $activity && $activity->photographer_price > 0) {
            $vatExclusivePrice = round((float) $activity->photographer_price / $vatMultiplier, 2);

            $items[] = [
                'id' => $lineId++,
                'name' => 'Photography Service',
                'quantity' => 1.0,
                'unit_price' => $vatExclusivePrice,
                'vat_category' => VatCategory::STANDARD,
                'vat_rate' => $vatRate,
                'unit_code' => 'E48', // Service unit
            ];
        }

        // Add tour guide service if included
        // Note: Activity model uses 'tourguide_price' (no underscore)
        if ($this->tour_guide && $activity && $activity->tourguide_price > 0) {
            $vatExclusivePrice = round((float) $activity->tourguide_price / $vatMultiplier, 2);

            $items[] = [
                'id' => $lineId++,
                'name' => 'Tour Guide Service',
                'quantity' => 1.0,
                'unit_price' => $vatExclusivePrice,
                'vat_category' => VatCategory::STANDARD,
                'vat_rate' => $vatRate,
                'unit_code' => 'E48', // Service unit
            ];
        }

        // Add tool rentals if included
        // tool_id and tool_capacity are stored as comma-separated values
        if (!empty($this->tool_id)) {
            $toolIds = array_filter(explode(',', $this->tool_id));
            $toolCapacities = !empty($this->tool_capacity)
                ? explode(',', $this->tool_capacity)
                : [];

            $tools = CommercialTool::whereIn('id', $toolIds)->get()->keyBy('id');

            foreach ($toolIds as $index => $toolId) {
                $tool = $tools->get((int) $toolId);
                if ($tool) {
                    $quantity = isset($toolCapacities[$index]) ? (float) $toolCapacities[$index] : 1.0;
                    $vatExclusivePrice = round((float) $tool->price / $vatMultiplier, 2);

                    $items[] = [
                        'id' => $lineId++,
                        'name' => $tool->name_en ?? $tool->name_ar ?? 'Tool Rental',
                        'quantity' => $quantity,
                        'unit_price' => $vatExclusivePrice,
                        'vat_category' => VatCategory::STANDARD,
                        'vat_rate' => $vatRate,
                        'unit_code' => 'C62', // Unit
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Get the subtotal (excluding VAT).
     *
     * Calculated from line items to ensure consistency with invoice.
     * Each line item's unit_price is already VAT-exclusive.
     */
    public function getInvoiceSubtotal(): float
    {
        $lineItems = $this->getInvoiceLineItems();
        $subtotal = 0.0;

        foreach ($lineItems as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        return round($subtotal, 2);
    }

    /**
     * Get the VAT amount.
     *
     * Calculated from line items to ensure consistency.
     * VAT = subtotal * VAT_RATE / 100
     */
    public function getInvoiceVatAmount(): float
    {
        $vatRate = $this->getDefaultVatRate();

        return round($this->getInvoiceSubtotal() * $vatRate / 100, 2);
    }

    /**
     * Get the total amount (including VAT).
     *
     * Calculated from subtotal + VAT to ensure consistency with line items.
     */
    public function getInvoiceTotal(): float
    {
        return round($this->getInvoiceSubtotal() + $this->getInvoiceVatAmount(), 2);
    }

    /**
     * Get buyer information.
     */
    public function getBuyerInfo(): ?array
    {
        // For B2C bookings, buyer info is optional
        // We still provide basic info for reference
        return [
            'name' => $this->user_name,
            'phone' => $this->phone_number,
        ];
    }

    /**
     * Determine if this is a B2B transaction.
     * Activity bookings are typically B2C (customers).
     */
    public function isB2BTransaction(): bool
    {
        // Check if customer has VAT number (indicating B2B)
        $customer = $this->customer;

        if ($customer && !empty($customer->vat_number)) {
            return true;
        }

        return false;
    }

    /**
     * Get the payment method used.
     */
    public function getPaymentMethod(): string
    {
        // Bookings are typically paid via HyperPay (card)
        return 'bank_card';
    }

    /**
     * Get the supply date (activity date).
     */
    public function getSupplyDate(): ?Carbon
    {
        if ($this->date) {
            return Carbon::parse($this->date);
        }

        return $this->created_at;
    }
}
