<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveOldBookings extends Command
{
    protected $signature = 'bookings:archive-old';

    protected $description = 'Archive bookings that passed their date';

    public function handle()
    {
        $todayDate = Carbon::today()->toDateString();

        Log::info('Archiving bookings before: ' . $todayDate);

        // 1 => Waiting , 2 => Rejected, 3 => Accepted , 4 => Cancelled by user , 5 => Paied , 6 => Cancelled by provider , 7 => Booking Completed , 8 => Archived
        $count = Booking::where('date', '<', $todayDate)
            ->where('status_id', '!=', 8)
            ->update([
                'status_id' => 8
            ]);

        $this->info("Archive complete. Count: {$count}");
    }
}
