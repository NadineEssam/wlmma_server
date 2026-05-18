<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Http\Requests\PushNotificationRequest;
use App\Models\Activity;
use App\Models\BillingInformation;
use App\Models\Booking;
use App\Services\CreditNoteService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRefundBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:auto-refund';

    /**
     * The console command description.
     *
     * @var string\App\Console\Commands\AutoRefundBookings::class,
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    private $authToken;

    public function __construct()
    {
        parent::__construct();

        $this->authToken = 'Bearer ' . env('HYPERPAY_AUTH_TOKEN');
    }

    public function handle(CreditNoteService $creditNoteService)
    {
        // $authToken = 'Bearer ' . env('HYPERPAY_AUTH_TOKEN');
        // Log::info('NOW ' . Carbon::now());
        // Log::info('NOW - 10 sec ' . Carbon::now()->subSeconds(10));
        Log::info('File: ' . __FILE__);
        $bookings = Booking::where('status_id', 4)  // Cancelled by user
            ->where('is_paied', true)
            ->where('is_returned', false)
            // ->where('updated_at', '<=', Carbon::now()->subSeconds(10))
            // ->where('updated_at', '<=', Carbon::now()->subHours(1))
            ->get();
        $this->info('Refunded bookings:');
        foreach ($bookings as $booking) {
            $this->line("Found booking ID: {$booking->id}");
            DB::transaction(function () use ($booking, $creditNoteService) {
                $user = $booking->customer;
                // Log::info($user);

                // ارجاع المبلغ
                $amount = $booking->total_price;  // أو استخدم returnd_amount إذا أضفته

                // Log::info('Amount ' . $amount);
                // Log::info('Before ' . $user->wallet->balance);
                // $wallet = $user->wallet;
                // // $user->save();
                // $wallet->update([
                //     'balance' => $wallet->balance + $amount
                // ]);
                // // Log::info('After ' . $user->wallet->balance);

                // // Record wallet transaction
                // $wallet->transactions()->create([
                //     'type' => 'credit',  //  Refund ايداع
                //     'user_id' => $user->id,
                //     'book_id' => $booking->id,
                //     'amount' => $amount,
                //     'reason' => 'Booking refund',
                //     'wallet_id' => $wallet->id,
                //     'provider_id' => auth()->id(),
                // ]);

                // $billData = BillingInformation::where('book_id', $booking->id)->first();
                // $paymentId = $billData ? $billData->payment_id : null;
                // $amount = $amount;
                // $payment_method = $billData ? $billData->payment_method : null;
                $billData = BillingInformation::where('book_id', $booking->id)->first();

                $paymentId = optional($billData)->payment_id;
                $payment_method = optional($billData)->payment_method;

                Log::info('billData REFUND CRON => ' . $billData);
                Log::info('paymentId REFUND CRON => ' . $paymentId);
                Log::info('amount REFUND CRON => ' . $amount);
                Log::info('payment_method REFUND CRON => ' . $payment_method);
                // TESTING CREDENTIALS
                // Mada => 8ac7a4ca94d62a8d0194dc62f63d103f
                // Visa & MasterCard => 8ac7a4ca94d62a8d0194dc625aa7103b
                // ApplePay => 8ac7a4da9b6c2358019b6ef75088052f
                // $entityId = '';
                $entityId = $billData->entity_id;
                Log::info('entity_id REFUND CRON => ' . $entityId);

                $client = new Client();
                $response = $client->post(
                    env('HYPERPAY_BASE_URL') . "payments/{$paymentId}",
                    [
                        // 'http_errors' => false,
                        'headers' => [
                            'Authorization' => $this->authToken,
                        ],
                        'form_params' => [
                            'entityId' => $entityId,
                            'amount' => $amount,
                            'currency' => 'SAR',
                            'paymentType' => 'RF',
                        ],
                    ]
                );

                Log::info(json_decode($response->getBody(), true));

                // $code = $response['result']['code'] ?? null;
                $responseBody = (string) $response->getBody();  // لو Guzzle Response object
                $responseData = json_decode($responseBody, true);  // نحوله لـ array

                // استخراج الكود
                $code = data_get($responseData, 'result.code', null);

                // تسجيله
                Log::info('CODEEEEEEEEEEEEEEEEEEEEEEE REFUND CRON => ' . $code);
                if (($code == '000.100.110' || $code == '000.100.101' || $code == '000.000.000')) {
                    $activity = Activity::find($booking->activity_id);
                    $booking->is_paied = false;
                    $booking->is_returned = true;
                    $booking->status_id = 9;
                    $booking->save();

                    // Generate ZATCA credit note for the refund
                    try {
                        $creditNoteService->createForBooking(
                            $booking,
                            $amount,
                            'Booking cancellation and refund'
                        );
                        Log::info('ZATCA credit note created for refunded booking', [
                            'booking_id' => $booking->id,
                            'amount' => $amount,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to create ZATCA credit note for refund', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $status = $booking->status_id;
                    $check_status = $request->status_id ? $request->status_id : $status;
                    $bookingStatus = BookingStatus::find($check_status);
                    $user_data = User::find($user->id);
                    sendViewEmail($user_data->email, $bookingStatus->status, 'emails.activity-status', [
                        'activityTitle' => $activity->title_en,
                        'status' => $bookingStatus->status,
                        'refundAmount' => $booking->total_price
                    ]);

                    $notificationData = [
                        'title_en' => 'Refund Issued',
                        'title_ar' => 'تم استرداد المبلغ',
                        'body_en' => 'Your booking has been canceled and an amount of ' . $booking->total_price . ' has been refunded to your account.',
                        'body_ar' => "تم إلغاء الحجز الخاص بك وتم رد مبلغ {$booking->total_price} إلى حسابك.",
                        'type' => 'wallet',
                        'book_id' => $booking->id ?? null,
                        'action' => 'activity & wallet',
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
                }
            });
        }

        $this->info('Auto refund complete. Count: ' . $bookings->count());
    }
}
