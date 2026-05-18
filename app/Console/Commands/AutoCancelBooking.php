<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Http\Requests\PushNotificationRequest;
use App\Models\Activity;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCancelBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:auto-cancel-booking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('NOW ' . Carbon::now());
        Log::info('NOW - 10 sec ' . Carbon::now()->subHours(24));
        $bookings = Booking::where('status_id', 3)  // Confirmed
            ->where('is_returned', false)
            ->where('is_paied', false)
            ->where('updated_at', '<=', Carbon::now()->subHours(24))
            ->get();
        // $this->info('Refunded bookings:');
        foreach ($bookings as $booking) {
            $this->line("Found booking ID: {$booking->id}");
            DB::transaction(function () use ($booking) {
                $user = $booking->customer;
                Log::info($user);
                $activity = Activity::find($booking->activity_id);

                // Delete booking
                $booking->update([
                    'status_id' => 2  // rejected automatically
                ]);

                $activity->update([
                    'capacity' => $activity->capacity + $booking->capacity  // return capacity automatically
                ]);

                // $booking->delete();

                $notificationData = [
                    'title_en' => 'Booking Rejected',
                    'title_ar' => 'تم إلغاء الحجز',
                    'body_en' => "Your booking has been rejected becacuse you didn't pay within 24 hours.",
                    'body_ar' => 'تم إلغاء الحجز الخاص بك لعدم الدفع فى خلال 24 ساعة',
                    'type' => 'reservation_rejected',
                    'action' => 'booking',
                    'send_to_all' => false,
                ];

                // dd($notificationData);
                // Call the push_book method from the notification controller
                $pushController = new NotificationController();  // Create an instance of the notification controller
                // foreach ($all_booking_users as $all_users) {
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD
                // $notificationData['user_id'] = $user->id;

                // $response = $pushController->push_welcome(
                //     new PushNotificationRequest($notificationData)
                // );
            });
        }

        $this->info('Auto cancel complete. Count: ' . $bookings->count());
    }
}
