<?php

namespace App\Jobs;

use App\Contracts\InvoiceableInterface;
use App\Enums\ZatcaStatus;
use App\Services\InvoiceService;
use Corecave\Zatca\Models\ZatcaInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to retry failed ZATCA invoice submissions.
 *
 * This job can be dispatched manually or scheduled to run periodically.
 * It finds all pending/rejected invoices and attempts to resubmit them.
 */
class RetryFailedInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Maximum number of invoices to process in a single run.
     */
    protected int $maxInvoices;

    /**
     * Specific invoice ID to retry (optional).
     */
    protected ?int $invoiceId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $invoiceId = null, int $maxInvoices = 50)
    {
        $this->invoiceId = $invoiceId;
        $this->maxInvoices = $maxInvoices;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoiceService $invoiceService): void
    {
        $query = ZatcaInvoice::query()
            ->whereIn('status', [
                ZatcaStatus::PENDING->value,
                ZatcaStatus::REJECTED->value,
            ])
            ->with('invoiceable');

        // If specific invoice ID provided, only retry that one
        if ($this->invoiceId) {
            $query->where('id', $this->invoiceId);
        }

        $failedInvoices = $query->limit($this->maxInvoices)->get();

        Log::info('RetryFailedInvoicesJob started', [
            'invoice_count' => $failedInvoices->count(),
            'specific_id' => $this->invoiceId,
        ]);

        $success = 0;
        $failed = 0;

        foreach ($failedInvoices as $zatcaInvoice) {
            try {
                $entity = $zatcaInvoice->invoiceable;

                if (!$entity || !($entity instanceof InvoiceableInterface)) {
                    Log::warning('RetryFailedInvoicesJob: Invalid or missing invoiceable entity', [
                        'invoice_id' => $zatcaInvoice->id,
                        'invoiceable_type' => $zatcaInvoice->invoiceable_type,
                        'invoiceable_id' => $zatcaInvoice->invoiceable_id,
                    ]);
                    $failed++;
                    continue;
                }

                // Attempt to reprocess the invoice
                $result = $invoiceService->processInvoice($entity);

                if ($result && $result->getResult()->isSuccess()) {
                    $success++;
                    Log::info('RetryFailedInvoicesJob: Invoice resubmitted successfully', [
                        'invoice_id' => $zatcaInvoice->id,
                        'invoice_number' => $zatcaInvoice->invoice_number,
                        'status' => $result->getType(),
                    ]);
                } else {
                    $failed++;
                    Log::warning('RetryFailedInvoicesJob: Invoice resubmission failed', [
                        'invoice_id' => $zatcaInvoice->id,
                        'invoice_number' => $zatcaInvoice->invoice_number,
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('RetryFailedInvoicesJob: Exception during retry', [
                    'invoice_id' => $zatcaInvoice->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('RetryFailedInvoicesJob completed', [
            'total_processed' => $failedInvoices->count(),
            'success' => $success,
            'failed' => $failed,
        ]);
    }
}
