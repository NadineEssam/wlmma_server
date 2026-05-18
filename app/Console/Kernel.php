<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\AutoRefundBookings::class,
        \App\Console\Commands\AutoRefundOrders::class,
        \App\Console\Commands\AutoCancelBooking::class,
        \App\Console\Commands\RetryFailedInvoices::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // $schedule->command('bookings:archive-old')->dailyAt('00:05');  // هيشتغل كل يوم بعد نص الليل
        // $schedule->command('bookings:archive-old')->everyMinute(); / for test
        $schedule->command('bookings:auto-refund')->everyMinute();
        $schedule->command('orders:auto-refund')->everyMinute();
        $schedule->command('bookings:auto-cancel-booking')->everyMinute();

        // Retry failed ZATCA invoices every hour
        $schedule->command('invoices:retry-failed --sync')->hourly();

        $schedule->call(function () {
            Log::info('Cron job executed successfully at: ' . now());
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
