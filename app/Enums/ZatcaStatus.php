<?php

namespace App\Enums;

/**
 * ZATCA invoice submission status.
 */
enum ZatcaStatus: string
{
    case PENDING = 'pending';
    case REPORTED = 'reported';      // Simplified invoice submitted successfully
    case CLEARED = 'cleared';        // Standard invoice cleared successfully
    case FAILED = 'failed';          // Submission failed
    case REJECTED = 'rejected';      // Rejected by ZATCA

    /**
     * Check if the invoice was successfully processed.
     */
    public function isSuccess(): bool
    {
        return in_array($this, [self::REPORTED, self::CLEARED]);
    }

    /**
     * Check if the invoice needs retry.
     */
    public function needsRetry(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::REPORTED => 'Reported',
            self::CLEARED => 'Cleared',
            self::FAILED => 'Failed',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get Arabic label.
     */
    public function labelAr(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::REPORTED => 'تم الإبلاغ',
            self::CLEARED => 'تم الاعتماد',
            self::FAILED => 'فشل',
            self::REJECTED => 'مرفوض',
        };
    }
}
