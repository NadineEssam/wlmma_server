<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedInvoicesJob;
use Illuminate\Console\Command;

/**
 * Console command to retry failed ZATCA invoice submissions.
 *
 * Can be scheduled in the kernel or run manually:
 * php artisan invoices:retry-failed
 * php artisan invoices:retry-failed --id=123
 */
class RetryFailedInvoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'invoices:retry-failed
                            {--id= : Specific invoice ID to retry}
                            {--max=50 : Maximum number of invoices to process}
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     */
    protected $description = 'Retry failed ZATCA invoice submissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $invoiceId = $this->option('id') ? (int) $this->option('id') : null;
        $maxInvoices = (int) $this->option('max');
        $sync = $this->option('sync');

        $this->info('Retrying failed ZATCA invoices...');

        if ($invoiceId) {
            $this->line("Retrying specific invoice ID: {$invoiceId}");
        } else {
            $this->line("Processing up to {$maxInvoices} failed invoices");
        }

        $job = new RetryFailedInvoicesJob($invoiceId, $maxInvoices);

        if ($sync) {
            $this->line('Running synchronously...');
            dispatch_sync($job);
        } else {
            $this->line('Dispatching to queue...');
            dispatch($job);
        }

        $this->info('Job dispatched successfully!');

        return self::SUCCESS;
    }
}
