<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Enums\AActivityStatusEnum;
use App\Events\ActivityAdded;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Resources\ActivityResource;
use App\Mail\ActivityStatusMail;
use App\Models\Activity;
use App\Models\ActivityAttendence;
use App\Models\ActivityCapacity;
use App\Models\ActivityImage;
use App\Models\ActivityPlan;
use App\Models\ActivityTool;
use App\Models\BillingInformation;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\CommercialTool;
use App\Models\Image;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Tool;
use App\Services\CreditNoteService;
// use App\Models\ToolAttribute;
// use App\Models\ToolAttributeValues;
use App\Models\ToolImage;
use App\Models\User;
use App\Models\WaitingList;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Activity\ActivityService;
use App\Services\Notification\NotificationManager;
use App\Services\InvoiceService;
use App\Services\WalletService;
use Carbon\CarbonPeriod;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    private $authToken;

    public function __construct(
        private ActivityService $service,
        private CreditNoteService $creditNoteService
    ) {
        $this->authToken = 'Bearer ' . env('HYPERPAY_AUTH_TOKEN');
    }

    // public function index(Request $request)
    // {

    //     $activities = $this->service->getAllServiceProviderActivities($request->per_page, $request->user()->id);

    //     return response()->json($activities);
    // }

    // Show activity for specific service provider
    public function index(Request $request)
    {
        $activities = $this->service->getAllServiceProviderActivities($request->per_page);

        return response()->json($activities);
    }

    // Show all requested activity for specific service provider
    public function show_booking_request(Request $request)
    {
        $activities = $this->service->get_booking($request->per_page, $request->status_id);

        return response()->json($activities);
    }

    public function booking_request_action(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:bookings,id',
            'status_id' => 'nullable|exists:booking_statuses,id',
            'returnd_amount' => 'nullable|numeric|min:0',
        ]);
        $notificationData = [];
        // Update booking status
        $booking = Booking::with(['customer.wallet'])->findOrFail($request->book_id);
        if ($request->status_id) {
            $booking->update(['status_id' => $request->status_id]);
        }
        $user = $booking->customer;
        $total_price = $booking->total_price;
        $wallet = $user?->wallet;
        $activity = Activity::find($booking->activity_id);
        $activity_price = $activity->price;
        $status = $booking->status_id;
        $check_status = $request->status_id ? $request->status_id : $status;
        $bookingStatus = BookingStatus::find($check_status);
        $bookingWithStatus = Booking::with('booking_status')->find($booking->id);
        if (($request->status_id == 6 || $request->status_id == 4 || $request->status_id == NULL) && $booking->is_paied == true) {
            $billData = BillingInformation::where('book_id', $request->book_id)->first();
            $paymentId = $billData ? $billData->payment_id : null;
            // $amount = $request->returnd_amount ? number_format($request->returnd_amount, 2, '.', '') : $billData->amount;
            $amount = $request->returnd_amount ? number_format($request->returnd_amount, 2, '.', '') : number_format($billData->amount, 2, '.', '');

            $payment_method = $billData ? $billData->payment_method : null;
            Log::info('billData => ' . $billData);
            Log::info('paymentId => ' . $paymentId);
            Log::info('amount => ' . $amount);
            Log::info('payment_method => ' . $payment_method);
            // TESTING CREDENTIALS
            // Mada => 8ac7a4ca94d62a8d0194dc62f63d103f
            // Visa & master => 8ac7a4ca94d62a8d0194dc625aa7103b
            // ApplePay => 8ac7a4da9b6c2358019b6ef75088052f
            $entityId = '';
            // if ($payment_method == 'visa' || $payment_method == 'master') {
            //     $entityId = '8ac7a4ca94d62a8d0194dc625aa7103b';
            // } else if ($payment_method == 'mada') {
            //     $entityId = '8ac7a4ca94d62a8d0194dc62f63d103f';
            // } else if ($payment_method == 'applepay') {
            //     $entityId = '8ac7a4da9b6c2358019b6ef75088052f';
            // }

            // PROD CREDENTIALS
            // Mada => 8acda4cd99c83f980199c8a344c90766
            // Visa & master => 8ac7a4ca94d62a8d0194dc625aa7103b
            // ApplePay => 8ac7a4da9b6c2358019b6ef75088052f

            // if ($payment_method == 'visa' || $payment_method == 'master') {
            //     $entityId = '8acda4cd99c83f980199c8a344c90766';
            // } else if ($payment_method == 'mada') {
            //     $entityId = '8acda4cd99c83f980199c8a344c90766';
            // } else if ($payment_method == 'applepay') {
            //     $entityId = '8ac7a4da9b6c2358019b6ef75088052f';
            // }

            $entity_id = $billData->entity_id;

            $client = new Client();
        }
        // Log::info("EntityIDxxxxxxxxxxxxxxxxxxxxxxxxx => ".$entity_id);

        // dd();
        // Refund on cards

        // 1 => Waiting , 2 => Rejected, 3 => Accepted , 4 => Cancelled by user , 5 => Paied , 6 => Cancelled by provider , 7 => Booking Completed

        // Refund logic only if cancelled by provider (status_id == 6)
        ///   1 => Waiting, 2 => Rejected, 3 => Accepted, 4 => Cancelled by user, 5 => paid,  6 => Cancelled by provider, 7 => Booking Completed , 9==> refunded
        // if ($wallet && $request->status_id == 6) {  // Cancelled by provider
        if ($request->status_id == 6) {  // Cancelled by provider
            if ($booking->is_paied == true) {
                Log::info('Cancelled by provider &&  Paied');
                // Check if a refund was already done for this booking
                // $alreadyRefunded = WalletTransaction::where('book_id', $booking->id)
                //     ->where('type', 'credit')
                //     ->where('reason', 'booking refund')
                //     ->exists();

                // if (!$alreadyRefunded) {
                //     // Update wallet balance
                //     $wallet->update([
                //         'balance' => $wallet->balance + $total_price
                //     ]);

                //     // Record wallet transaction
                //     $wallet->transactions()->create([
                //         'type' => 'credit',
                //         'user_id' => $user->id,
                //         'book_id' => $booking->id,
                //         'amount' => $total_price,
                //         'reason' => 'Booking refund',
                //         'wallet_id' => $wallet->id,
                //         'provider_id' => auth()->id(),
                //     ]);

                // Refund on cards
                $response = $client->post(
                    env('HYPERPAY_BASE_URL') . "payments/{$paymentId}",
                    [
                        // 'http_errors' => false,
                        'headers' => [
                            'Authorization' => $this->authToken,
                        ],
                        'form_params' => [
                            'entityId' => $entity_id,
                            'amount' => $amount,
                            'currency' => 'SAR',
                            'paymentType' => 'RF',
                        ],
                    ]
                );

                Log::info(json_decode($response->getBody(), true));
                $responseBody = (string) $response->getBody();  // لو Guzzle Response object
                $responseData = json_decode($responseBody, true);  // نحوله لـ array

                // استخراج الكود
                $code = data_get($responseData, 'result.code', null);

                Log::info('Access Token => ' . env('HYPERPAY_AUTH_TOKEN'));
                // تسجيله
                Log::info('CODEEEEEEEEEEEEEEEEEEEEEEE => ' . $code);
                // $code = $response['result']['code'] ?? null;
                // $code = data_get($response, 'result.code');
                // $code = data_get($response, 'result.code', null);
                // Log::info("CODEEEEEEEEEEEEEEEEEEEEEEE => ". $code);

                if (($code == '000.100.110' || $code == '000.100.101' || $code == '000.000.000')) {
                    // $booking->update([
                    //     'status_id' => $request->status_id,
                    //     'is_paied' => false,
                    //     'is_returned' => true,
                    //     'status_id' => 9,
                    // ]);

                    $booking->is_returned = true;
                    $booking->is_paied = false;
                    $booking->status_id = 9;
                    $booking->save();

                    // Generate ZATCA credit note for the refund

                    try {
                        $this->creditNoteService->createForBooking(
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

                    // Send notification to this user only with booking status and wallet balance
                    $notificationData = [
                        'title_en' => "The {$activity->title_en} is {$bookingStatus->status}",
                        'title_ar' => "ال{$activity->title_ar} ({$bookingStatus->status})",
                        'body_en' => "The {$activity->title_en} is {$bookingStatus->status}. Your wallet balance is now: " . number_format($wallet->balance, 2) ?? 0.0 . ' SAR',
                        'body_ar' => "ال{$activity->title_ar} ({$bookingStatus->status}). رصيد محفظتك الآن: " . number_format($wallet->balance, 2) ?? 0.0 . ' ريال',
                        'type' => 'wallet',
                        'book_id' => $request->book_id,
                        'action' => 'activity & wallet',
                        'send_to_all' => false,
                    ];

                    $pushController = new NotificationController();
                    $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD

                    // $notificationData['user_id'] = $user->id;

                    // $response = $pushController->push_welcome(
                    //     new PushNotificationRequest($notificationData)
                    // );

                    $user_email = User::where('id', $user->id)->value('email');

                    if ($user_email) {
                        try {
                            // Mail::to($user_email)->send(new ActivityStatusMail(
                            //     activityTitle: $activity->title_en,
                            //     status: $bookingStatus->status,
                            //     refundAmount: number_format($wallet->balance, 2)
                            // ));

                            sendViewEmail($user_email, $bookingStatus->status, 'emails.activity-status', [
                                'activityTitle' => $activity->title_en,
                                'status' => $bookingStatus->status,
                                'refundAmount' => $amount
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send activity status emailssssssssssssssssssssssssssssssssss: ' . $e->getMessage());
                        }
                    }
                }
                // } else {
                //     return response()->json([
                //         'status' => 'success',
                //         'message' => 'You already refunded this booking before',
                //         'message_ar' => 'تم رد هذا الحجز مسبقاً',
                //     ], 200);
                // }
            } else {
                Log::info('Cancelled by provider && NOT Paied');
                $booking->update([
                    'status_id' => $request->status_id,
                ]);
                // Send notification to this user only with booking status and wallet balance
                $notificationData = [
                    'title_en' => "The {$activity->title_en} is {$bookingStatus->status}",
                    'title_ar' => "ال{$activity->title_ar} ({$bookingStatus->status})",
                    'body_en' => "The {$activity->title_en} is {$bookingStatus->status}.",
                    'body_ar' => "ال{$activity->title_ar} ({$bookingStatus->status}). ",
                    'type' => 'wallet',
                    'book_id' => $request->book_id,
                    'action' => 'activity & wallet',
                    'send_to_all' => false,
                ];

                $pushController = new NotificationController();
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD

                // $notificationData['user_id'] = $user->id;

                // $response = $pushController->push_welcome(
                //     new PushNotificationRequest($notificationData)
                // );

                $user_email = User::where('id', $user->id)->value('email');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new ActivityStatusMail(
                        //     activityTitle: $activity->title_en,
                        //     status: $bookingStatus->status
                        // ));

                        sendViewEmail($user_email, $bookingStatus->status, 'emails.activity-status', [
                            'activityTitle' => $activity->title_en,
                            'status' => $bookingStatus->status
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send activity status email:3333333333333333333333333333333333 ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'message' => "User did't pay for this book there is no refund",
                    'message_ar' => 'المستخدم لم يدفع لهذا الحجز لا يوجد نقود مرجعة',
                    'data' => $bookingWithStatus,
                ], 200);
            }
            // } else if ($wallet && $status == 4 && $request->returnd_amount > 0) {  // Cancelled by user
            
            ///   1 => Waiting, 2 => Rejected, 3 => Accepted, 4 => Cancelled by user, 5 => paid,  6 => Cancelled by provider, 7 => Booking Completed , 9==> refunded
        } else if ($status == 4 && $request->returnd_amount > 0) {  // Cancelled by user
            if ($booking->is_paied == true) {
                Log::info('Cancelled by user &&  Paied');
                Log::info('EntityIDttttttttttttttttttttttt => ' . $entity_id);
                // Check if a refund was already done for this booking
                // $alreadyRefunded = WalletTransaction::where('book_id', $booking->id)
                //     ->where('type', 'credit')
                //     ->where('reason', 'booking refund')
                //     ->exists();

                // if (!$alreadyRefunded) {
                // Update wallet balance
                // $wallet->update([
                //     'balance' => $wallet->balance + $request->returnd_amount
                // ]);

                // // Record wallet transaction
                // $wallet->transactions()->create([
                //     'type' => 'credit',
                //     'user_id' => $user->id,
                //     'book_id' => $booking->id,
                //     'amount' => $request->returnd_amount,
                //     'reason' => 'Booking refund',
                //     'wallet_id' => $wallet->id,
                //     'provider_id' => auth()->id(),
                // ]);

                if (!$entity_id || !$paymentId || !$amount) {
                    Log::error('Missing required refund parameters', [
                        'entity_id' => $entity_id,
                        'paymentId' => $paymentId,
                        'amount' => $amount,
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Missing parameters'
                    ], 400);
                }

                // Refund on cards
                $response = $client->post(
                    env('HYPERPAY_BASE_URL') . "payments/{$paymentId}",
                    [
                        // 'http_errors' => false,
                        'headers' => [
                            'Authorization' => $this->authToken,
                        ],
                        'form_params' => [
                            'entityId' => $entity_id,
                            'amount' => $amount,
                            'currency' => 'SAR',
                            'paymentType' => 'RF',
                        ],
                    ]
                );

                Log::info('EntityIDqqqqqqqqqqq => ' . $entity_id);

                Log::info(json_decode($response->getBody(), true));

                // $code = $response['result']['code'] ?? null;
                $responseBody = (string) $response->getBody();  // لو Guzzle Response object
                $responseData = json_decode($responseBody, true);  // نحوله لـ array

                // استخراج الكود
                $code = data_get($responseData, 'result.code', null);

                // تسجيله
                Log::info('CODEEEEEEEEEEEEEEEEEEEEEEE => ' . $code);

                if (($code == '000.100.110' || $code == '000.100.101' || $code == '000.000.000')) {
                    // $booking->update([
                    //     'is_returned' => true,
                    //     'is_paied' => false,
                    //     'status_id' => 9,
                    // ]);

                    $booking->is_returned = true;
                    $booking->is_paied = false;
                    $booking->status_id = 9;
                    $booking->save();

                    // Generate ZATCA credit note for the refund
                    try {
                        $this->creditNoteService->createForBooking(
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

                    // Send notification to this user only with booking status and wallet balance
                    $notificationData = [
                        'title_en' => "The {$activity->title_en} is {$bookingStatus->status}",
                        'title_ar' => "ال{$activity->title_ar} ({$bookingStatus->status})",
                        'body_en' => "The {$activity->title_en} is {$bookingStatus->status}. Your wallet balance is now: " . number_format($wallet->balance, 2) ?? 0.0 . ' SAR',
                        'body_ar' => "ال{$activity->title_ar} ({$bookingStatus->status}). رصيد محفظتك الآن: " . number_format($wallet->balance, 2) ?? 0.0 . ' ريال',
                        'type' => 'wallet',
                        'book_id' => $request->book_id ?? null,
                        'action' => 'activity & wallet',
                        'send_to_all' => false,
                    ];

                    $pushController = new NotificationController();
                    $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD

                    // $notificationData['user_id'] = $user->id;

                    // $response = $pushController->push_welcome(
                    //     new PushNotificationRequest($notificationData)
                    // );

                    $user_email = User::where('id', $user->id)->value('email');

                    if ($user_email) {
                        try {
                            // Mail::to($user_email)->send(new ActivityStatusMail(
                            //     activityTitle: $activity->title_en,
                            //     status: $bookingStatus->status,
                            //     refundAmount: number_format($wallet->balance, 2)
                            // ));

                            sendViewEmail($user_email, $bookingStatus->status, 'emails.activity-status', [
                                'activityTitle' => $activity->title_en,
                                'status' => $bookingStatus->status . ' and refunded',
                                'refundAmount' => $amount
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send activity status emailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx: ' . $e->getMessage());
                        }
                    }
                }
                // } else {
                //     return response()->json([
                //         'status' => 'success',
                //         'message' => 'You already refunded this booking before',
                //         'message_ar' => 'تم رد هذا الحجز مسبقاً',
                //     ], 200);
                // }
            } else {
                Log::info('Cancelled by user && NOT Paied');
                // Send notification to this user only with booking status and wallet balance
                $notificationData = [
                    'title_en' => "The {$activity->title_en} is {$bookingStatus->status}",
                    'title_ar' => "ال{$activity->title_ar} ({$bookingStatus->status})",
                    'body_en' => "The {$activity->title_en} is {$bookingStatus->status}. ",
                    'body_ar' => "ال{$activity->title_ar} ({$bookingStatus->status}). ",
                    'type' => 'wallet',
                    'book_id' => $request->book_id ?? null,
                    'action' => 'activity & wallet',
                    'send_to_all' => false,
                ];

                $pushController = new NotificationController();
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD

                // $notificationData['user_id'] = $user->id;

                // $response = $pushController->push_welcome(
                //     new PushNotificationRequest($notificationData)
                // );

                $user_email = User::where('id', $user->id)->value('email');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new ActivityStatusMail(
                        //     activityTitle: $activity->title_en,
                        //     status: $bookingStatus->status
                        // ));

                        sendViewEmail($user_email, $bookingStatus->status, 'emails.activity-status', [
                            'activityTitle' => $activity->title_en,
                            'status' => $bookingStatus->status
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send activity status email:4444444444444444444444444444444444444 ' . $e->getMessage());
                    }
                }
                return response()->json([
                    'status' => 'success',
                    'message' => "User did't pay for this book there is no refund",
                    'message_ar' => 'المستخدم لم يدفع لهذا الحجز لا يوجد نقود مرجعة',
                    'data' => $bookingWithStatus,
                ], 200);
            }
        } else {
            // 1 => Waiting , 2 => Rejected, 3 => Accepted
            ///   1 => Waiting, 2 => Rejected, 3 => Accepted, 4 => Cancelled by user, 5 => paid,  6 => Cancelled by provider, 7 => Booking Completed , 9==> refunded
            Log::info(' ELSE ');
            $booking->update([
                'status_id' => $request->status_id,
            ]);
            if ($request->status_id == 3) {
                Log::info('ACCEPTED');
                // Send notification to this user only with booking status and wallet balance
                $notificationData = [
                    'title_en' => "The {$activity->title_en} is {$bookingStatus->status}",
                    'title_ar' => "ال{$activity->title_ar} ({$bookingStatus->status})",
                    'body_en' => "The {$activity->title_en} is {$bookingStatus->status}. Please continue your payment.",
                    'body_ar' => "ال{$activity->title_ar} هي {$bookingStatus->status}. يرجى متابعة عملية الدفع.",
                    'type' => 'wallet',
                    'book_id' => $request->book_id,
                    'action' => 'activity & wallet',
                    'send_to_all' => false,
                ];
            } else if ($request->status_id == 2) {
                Log::info('Rejected');
                // Send notification to this user only with booking status and wallet balance
                $notificationData = [
                    'title_en' => "The {$activity->title_en} is {$bookingStatus->status}",
                    'title_ar' => "ال{$activity->title_ar} ({$bookingStatus->status})",
                    'body_en' => "The {$activity->title_en} is {$bookingStatus->status}.",
                    'body_ar' => "ال{$activity->title_ar} هي {$bookingStatus->status}.",
                    'type' => 'wallet',
                    'book_id' => $request->book_id,
                    'action' => 'activity & wallet',
                    'send_to_all' => false,
                ];
            }

            $pushController = new NotificationController();
            $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD

            // $notificationData['user_id'] = $user->id;

            // $response = $pushController->push_welcome(
            //     new PushNotificationRequest($notificationData)
            // );
        }
        $user_email = User::where('id', $user->id)->value('email');

        if ($user_email) {
            try {
                // Mail::to($user_email)->send(new ActivityStatusMail(
                //     activityTitle: $activity->title_en,
                //     status: $bookingStatus->status
                // ));

                sendViewEmail($user_email, $bookingStatus->status, 'emails.activity-status', [
                    'activityTitle' => $activity->title_en,
                    'status' => $bookingStatus->status
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send activity status email11111111111111111111111111: ' . $e->getMessage());
            }
        }
        return response()->json([
            'message' => 'Booking Request has been updated successfully',
            'message_ar' => 'تم تحديث طلب الحجز بنجاح',
            'data' => $bookingWithStatus,
        ], 200);
    }

    public function qrcode_search(Request $request)
    {
        $request->validate([
            'code' => 'required|exists:bookings,code',
        ]);

        // Fetch the booking by code and ensure it belongs to the authenticated user's activity
        $booking = Booking::with([
            'activity.activityImages',
            'activity.activityType',
            'activity.rate.user',
            'activity.activityTools.toolImages',
            'customer',
            'booking_status',
        ])
            ->where('code', $request->code)
            ->whereHas('activity', fn($query) => $query->where('user_id', auth()->id()))
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Data related to QR Code does not belong to you',
                'message_ar' => 'البيانات المتعلقة برمز الاستجابة السريعة لا تخصك',
            ], 404);
        }

        $activity = $booking->activity;

        // Calculate average rating
        $ratings = $activity->rate->pluck('rating');
        $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0;
        $activity->average_rating = number_format($averageRating, 4, '.', ',');

        // Map time ago info
        $activity->rate = $activity->rate->map(function ($rate) {
            $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();
            $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
            return $rate;
        });

        // Process available times
        $availableTimes = [];
        $days = explode(',', $activity->activity_days ?? '');
        $starts = explode(',', $activity->activity_times_start ?? '');
        $ends = explode(',', $activity->activity_times_end ?? '');
        $singleDates = explode(',', $activity->activity_single_dates ?? '');

        foreach ($singleDates as $dateString) {
            try {
                $date = Carbon::parse($dateString);
                $dayName = strtolower($date->format('l'));

                foreach ($days as $index => $day) {
                    if (strtolower(trim($day)) === $dayName) {
                        $availableTimes[] = [
                            'date' => $date->toDateString(),
                            'day_name' => $date->format('l'),
                            'start_time' => $starts[$index] ?? null,
                            'end_time' => $ends[$index] ?? null,
                        ];
                        break;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $activity->available_times = $availableTimes;

        // Fetch and attach plans, tools, attributes, and values

        $activityPlans = ActivityPlan::where('activity_id', $activity->id)->get();
        $activity->activity_plans = $activityPlans;

        $activityIds = [$booking->id];  // Since this is a single activity, we only need its ID
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        $tools = $activityTools->where('activity_id', $booking->id);

        $activity->activity_tools = $activityTools->map(function ($tool) {
            $tool->tool_images = $tool->toolImages->map(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            })->values();  // Ensure tool_images is an indexed array

            return $tool;
        })->values();  // Ensure activity_tools is an indexed array

        // Attach full image paths
        if ($activity->relationLoaded('activityImages')) {
            $activity->activityImages->transform(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            });
        }

        // ActivityAttendence::where('activity_id', $activity->id)
        //     ->where('user_id', $booking->user_id)
        //     ->update([
        //         'attendence' => 1,
        //     ]);

        // $attend = ActivityAttendence::where('activity_id', $activity->id)
        //     ->get();

        Booking::where('code', $request->code)->update([
            'attendence' => 1,
            'status_id' => 7
        ]);

        return response()->json([
            'message' => 'Data related to QR Code',
            'message_ar' => 'البيانات المتعلقة برمز الاستجابة السريعة',
            'data' => $activity,
            // 'attendence' => $attend,
        ]);
    }

    public function store(StoreActivityRequest $request)
    {
        DB::beginTransaction();

        // try {
        // Get the days from the request (e.g., [2, 5, 9])
        $days = $request->input('start_date');

        $activity_times_start = [];
        $activity_times_end = [];
        $activity_days = [];
        $activity_capacity_input = $request->capacity;

        if ($request->plan_activity == 'no') {  // single activity
            $activity_times_start = array_filter($request->input('activity_times_start') ?? []);
            $activity_times_end = array_filter($request->input('activity_times_end') ?? []);
            $activity_days = array_filter($request->input('activity_days') ?? []);
            $activityDays = is_array($activity_days) ? $activity_days : explode(',', $activity_days);
            // $activity_capacity_input = explode(',', $activity_capacity_input);
        }

        // Prepare activity days and times
        $activityDays = $activity_days ? (is_array($activity_days) ? $activity_days : explode(',', $activity_days)) : [];
        $activityTimesStart = $activity_times_start ? (is_array($activity_times_start) ? $activity_times_start : explode(',', $activity_times_start)) : [];
        $activityTimesEnd = $activity_times_end ? (is_array($activity_times_end) ? $activity_times_end : explode(',', $activity_times_end)) : [];

        $availableTimes = [];
        $activity_single_dates = '';

        // Generate dates for the entire year starting from the first start date
        if (!empty($activityDays) && !empty($activityTimesStart)) {
            // Get the first start date (parse the first date if multiple provided)
            $firstDate = is_array($days) ? $days[0] : $days;
            $startDate = Carbon::parse(trim($firstDate));
            $endDate = $startDate->copy()->addYear();  // Generate for 1 year from start date

            // Create a period for each day in the year
            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($period as $date) {
                $dayName = strtolower($date->format('l'));

                // Check if this day is in our activity days
                foreach ($activityDays as $index => $day) {
                    if (strtolower(trim($day)) === $dayName) {
                        $timeIndex = $index;

                        if (isset($activityTimesStart[$timeIndex])) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$timeIndex],
                                'end_time' => $activityTimesEnd[$timeIndex] ?? null,
                                'capacity' => $activity_capacity_input,
                                'source' => 'generated_from_input',
                            ];
                        }
                    }
                    $activity_single_dates = array_column($availableTimes, 'date');
                }
            }
        }
        // $activityData['activity_single_dates'] = json_encode($availableTimes);
        // dd($activity_single_dates);
        // Now build the activity data array
        $activityData = [
            'user_id' => auth()->id(),
            'user_type' => User::find(auth()->id())->userType->type,
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'spoken_lang' => $request->spoken_lang,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'city_name_en' => $request->city_name_en,
            'city_name_ar' => $request->city_name_ar,
            'country_name_en' => $request->country_name_en,
            'country_name_ar' => $request->country_name_ar,
            'privacy_policy_en' => $request->privacy_policy_en,
            'privacy_policy_ar' => $request->privacy_policy_ar,
            'cancel_policy_en' => $request->cancel_policy_en,
            'cancel_policy_ar' => $request->cancel_policy_ar,
            'duration' => $request->duration,
            'plan_activity' => $request->plan_activity,
            'price' => $request->price,
            'capacity' => $request->capacity,
            'status_id' => AActivityStatusEnum::ACTIVE,
            'lat' => $request->lat,
            'long' => $request->long,
            'is_tourguideable' => $request->is_tourguideable,
            'tourguide_price' => $request->tourguide_price,
            'activity_type_id' => $request->activity_type_id,
            // 'start_date' => is_array($days) ? implode(',', $days) : $days,
            'start_date' => $days,
            'is_photographer_available' => $request->is_photographer_available,
            'photographer_price' => $request->photographer_price,
            'activity_days' => is_array($request->activity_days) && count($request->activity_days) > 0
                ? implode(',', $request->activity_days)
                : null,
            'activity_times_start' => is_array($activity_times_start) && count($activity_times_start) > 0
                ? implode(',', $activity_times_start)
                : null,
            'activity_times_end' => is_array($activity_times_end) && count($activity_times_end) > 0
                ? implode(',', $activity_times_end)
                : null,
            // 'activity_single_dates' => json_encode($activity_single_dates), // Store the generated available times
            'activity_single_dates' => is_array($activity_single_dates) ? implode(',', $activity_single_dates) : $activity_single_dates,
        ];

        $activity = Activity::create($activityData);
        if ($request->plan_activity == 'no') {
            $activity_single_dates_new = collect($activity_single_dates)
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->toArray();

            foreach ($activity_single_dates_new as $date) {
                ActivityCapacity::create([
                    'activity_id' => $activity->id,
                    'capacity' => $request->capacity,
                    'date' => $date,  // string واحد
                    'day_name' => Carbon::parse($date)->format('l'),
                ]);
            }
        }

        // [Rest of your existing code for activity plans, images, tools, etc...]
        // Add activity plans if provided
        if ($request->filled('activity_plans')) {
            $activityPlansData = [];

            foreach ($request->activity_plans as $index => $plan) {
                // $day = isset($days[$index]) ? Carbon::parse($days[$index])->format('j') : null;
                $day = isset($days[$index]) && strtotime($days[$index]) !== false
                    ? Carbon::parse($days[$index])->format('j')
                    : null;

                $activityPlansData[] = [
                    'activity_id' => $activity->id,
                    'city_name_en' => $plan['location_en'],
                    'city_name_ar' => $plan['location_ar'],
                    'starts_at' => $plan['starts_at'],
                    'ends_at' => $plan['ends_at'],
                    'dates' => $days,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ActivityPlan::insert($activityPlansData);
        }

        // Handle images
        // if ($request->hasFile('images')) {
        //     $images = $request->file('images');
        //     $imagePaths = [];

        //     foreach ($images as $image) {
        //         $filename = uniqid() . '.' . $image->getClientOriginalExtension();
        //         $destinationPath = storage_path('app/public/activities/' . $filename);

        //         list($width, $height) = getimagesize($image);
        //         $newWidth = 800;
        //         $newHeight = intval($height * ($newWidth / $width));

        //         $sourceImage = match ($image->getClientOriginalExtension()) {
        //             'jpg', 'jpeg' => imagecreatefromjpeg($image),
        //             'png' => imagecreatefrompng($image),
        //             'webp' => imagecreatefromwebp($image),
        //             default => null,
        //         };

        //         if ($sourceImage) {
        //             $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        //             imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        //             switch ($image->getClientOriginalExtension()) {
        //                 case 'jpg':
        //                 case 'jpeg':
        //                     imagejpeg($resizedImage, $destinationPath, 75);
        //                     break;
        //                 case 'png':
        //                     imagepng($resizedImage, $destinationPath, 6);
        //                     break;
        //                 case 'webp':
        //                     imagewebp($resizedImage, $destinationPath, 75);
        //                     break;
        //             }

        //             imagedestroy($sourceImage);
        //             imagedestroy($resizedImage);

        //             $path = 'activities/' . $filename;

        //             $imagePaths[] = [
        //                 'image_path' => $path,
        //                 'created_at' => now(),
        //                 'updated_at' => now(),
        //             ];
        //         }
        //     }

        //     Image::insert($imagePaths);

        //     $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
        //     $ids = range($lastId - count($imagePaths) + 1, $lastId);

        //     $imageActivity = [];
        //     foreach ($ids as $id) {
        //         $imageActivity[] = [
        //             'image_id' => $id,
        //             'activity_id' => $activity->id,
        //             'created_at' => now(),
        //             'updated_at' => now(),
        //         ];
        //     }

        //     ActivityImage::insert($imageActivity);
        // }
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imagePaths = [];

            // foreach ($images as $image) {
            //     // Generate filename as WebP
            //     $filename = uniqid() . '.webp';

            //     $destinationPath = storage_path('app/public/activities/' . $filename);

            //     // تأكد إن الفولدر موجود
            //     if (!file_exists(dirname($destinationPath))) {
            //         mkdir(dirname($destinationPath), 0755, true);
            //     }

            //     // dd($destinationPath);
            //     // Get original dimensions
            //     list($width, $height) = getimagesize($image);

            //     // Set max width to 1280px (only resize if larger)
            //     $newWidth = ($width > 1280) ? 1280 : $width;
            //     $newHeight = intval($height * ($newWidth / $width));

            //     // Create source image based on file type
            //     $extension = strtolower($image->getClientOriginalExtension());
            //     $sourceImage = match ($extension) {
            //         'jpg', 'jpeg' => imagecreatefromjpeg($image),
            //         'png' => imagecreatefrompng($image),
            //         'webp' => imagecreatefromwebp($image),
            //         default => null,
            //     };

            //     if ($sourceImage) {
            //         // Resize the image
            //         $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            //         imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            //         // Compress and save as Lossy WebP with 80% quality
            //         imagewebp($resizedImage, $destinationPath, 80);

            //         // Free memory
            //         imagedestroy($sourceImage);
            //         imagedestroy($resizedImage);

            //         // Save path for DB
            //         $path = 'activities/' . $filename;

            //         $imagePaths[] = [
            //             'image_path' => $path,
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ];
            //     }
            // }

            foreach ($images as $image) {
                $filename = uniqid() . '.webp';
                $destinationPath = storage_path('app/public/activities/' . $filename);

                // تأكد إن الفولدر موجود
                if (!file_exists(dirname($destinationPath))) {
                    mkdir(dirname($destinationPath), 0755, true);
                }

                // المسار المؤقت للصورة
                $imagePath = $image->getPathname();

                // المقاسات الأصلية
                [$width, $height] = getimagesize($imagePath);

                // Resize لو أكبر من 1280
                $newWidth = ($width > 1280) ? 1280 : $width;
                $newHeight = intval($height * ($newWidth / $width));

                // نوع الصورة
                $extension = strtolower($image->getClientOriginalExtension());

                $sourceImage = match ($extension) {
                    'jpg', 'jpeg' => imagecreatefromjpeg($imagePath),
                    'png' => imagecreatefrompng($imagePath),
                    // 'webp'       => imagecreatefromwebp($imagePath),
                    default => null,
                };

                if (!$sourceImage) {
                    continue;
                }

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // الحفاظ على الشفافية للـ PNG
                if ($extension === 'png') {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                    $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                    imagefill($resizedImage, 0, 0, $transparent);
                }

                imagecopyresampled(
                    $resizedImage,
                    $sourceImage,
                    0, 0, 0, 0,
                    $newWidth,
                    $newHeight,
                    $width,
                    $height
                );

                // حفظ WebP بجودة 80%
                imagewebp($resizedImage, $destinationPath, 80);

                imagedestroy($sourceImage);
                imagedestroy($resizedImage);

                $imagePaths[] = [
                    'image_path' => 'activities/' . $filename,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Save all images
            Image::insert($imagePaths);

            // Link to activity
            $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
            $ids = range($lastId - count($imagePaths) + 1, $lastId);

            $imageActivity = [];
            foreach ($ids as $id) {
                $imageActivity[] = [
                    'image_id' => $id,
                    'activity_id' => $activity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ActivityImage::insert($imageActivity);
        }

        // Handle tools
        if ($request->filled('tool_id')) {
            foreach ($request->tool_id as $toolId) {
                ActivityTool::create([
                    'commercial_tool_id' => $toolId,
                    'activity_id' => $activity->id,
                ]);
            }
        }

        DB::commit();

        // Re-fetch the activity with all relations
        $newActivity = Activity::with(['activityImages', 'activityPlans', 'activityTools', 'activityTools.toolImages', 'capacities'])
            ->select(
                'activities.*',
                'activity_statuses.status'
            )
            ->where('user_id', auth()->id())
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->findOrFail($activity->id);

        // Format the available times for the response
        if (!empty($newActivity->available_times)) {
            $newActivity->available_times = json_decode($newActivity->available_times, true);
        } else {
            $newActivity->available_times = $availableTimes;
        }

        // Format image URLs
        $newActivity->activityImages->each(function ($image) {
            $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
                ? $image->image_path
                : env('APP_URL') . 'storage/app/public/' . $image->image_path;
        });

        // Format tool images
        // foreach ($newActivity->tools as $tool) {
        //     if ($tool->activityTools && $tool->activityTools->images) {
        //         $tool->commercialTool->images->each(function ($image) {
        //             $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
        //                 ? $image->image_path
        //                 : env('APP_URL') . 'storage/app/public/' . $image->image_path;
        //         });
        //     }
        // }

        $activityTools = ActivityTool::where('activity_id', $newActivity->id)->get();

        $toolIds = $activityTools->pluck('commercial_tool_id');

        $tools = CommercialTool::whereIn('id', $toolIds)->with('toolImages')->get();

        $tools->transform(function ($tool) {
            // Convert tool_images to an indexed array with full image path
            $tool->tool_images = $tool->toolImages->map(function ($image) {
                $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
                    ? $image->image_path
                    : rtrim(env('APP_URL'), '/') . '/storage/app/public/' . ltrim($image->image_path, '/');
                return $image;
            })->values();

            return $tool;
        });

        // Attach transformed tools to the activity
        $newActivity->tools = $tools->values();

        // Send notification
        // $notificationData = [
        //     'title_en' => 'New Trip Added',
        //     'title_ar' => 'تم إضافة رحلة جديد',
        //     'body_en' => 'A new trip has been added to the system.',
        //     'body_ar' => 'تم إضافة رحلة جديد إلى النظام.',
        //     'type' => 'general',
        //     'action' => 'activity',
        //     'send_to_all' => true,
        // ];

        // $pushController = new NotificationController();
        // $pushController->push(new PushNotificationRequest($notificationData));

        return response()->json([
            'message' => 'Trip created successfully',
            'message_ar' => 'تم إنشاء الرحلة بنجاح',
            'data' => $newActivity
        ], 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     info($e);
        //     return response()->json([
        //         'message' => 'An error occurred while processing your request.',
        //         'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }
    }

    /**
     * Generate dates for the specified days across multiple months and years.
     *
     * @param array $days Array of days (e.g., [2, 5, 9])
     * @return array Array of generated dates (e.g., ["2025-01-02", "2025-01-05", "2025-01-09", ...])
     */
    // ORIGINAL
    // private function generateDatesForDays(array $days,$duration): array
    // {
    //     $startDate = now()->startOfMonth();
    //     $endDate = $startDate->copy()->addYears(2);
    //     $generatedDates = [];
    //     while ($startDate->lte($endDate)) {
    //         foreach ($days as $day) {
    //             if (checkdate($startDate->month, $day, $startDate->year)) {
    //                 $generatedDates[] = Carbon::create($startDate->year, $startDate->month, $day)->toDateString();
    //             }
    //         }
    //         $startDate->addMonth();
    //     }
    //     return $generatedDates;
    // }
    private function generateDatesForDays(Carbon|string $day, int $duration, $activity_type): array
    {
        // Ensure $day is a Carbon instance and get start of its month
        $startDate = is_string($day) ? Carbon::parse($day)->startOfMonth() : $day->copy()->startOfMonth();

        $endDate = $startDate->copy()->addYears(2);
        $startDay = (int) Carbon::parse($day)->day;
        $startDay_single = $day;

        $generatedDates = [];
        if ($activity_type == 'no') {
            $generatedDates = [$startDay_single];  // now it's an array
        } else {
            // while ($startDate->lte($endDate)) {
            for ($i = 0; $i < $duration; $i++) {
                $targetDay = $startDay + $i;

                if (checkdate($startDate->month, $targetDay, $startDate->year)) {
                    $generatedDates[] = Carbon::create(
                        $startDate->year,
                        $startDate->month,
                        $targetDay
                    )->toDateString();
                }
            }
            // $startDate->addMonth();
            // }
        }
        // dd($generatedDates);
        return $generatedDates;
    }

    // Show Specific Activity data
    public function show($id)
    {
        try {
            // Fetch the activity with related data
            $activity = Activity::with('activityImages', 'activityType')
                ->select(
                    'activities.id',
                    'activities.user_id',
                    'activity_type_id',
                    'title_en',
                    'title_ar',
                    'description_en',
                    'description_ar',
                    'city_name_en',
                    'city_name_ar',
                    'country_name_en',
                    'country_name_ar',
                    'duration',
                    'plan_activity',
                    'start_date',
                    'price',
                    'activities.spoken_lang',
                    'capacity',
                    'status',
                    'lat',
                    'long',
                    'is_tourguideable',
                    'tourguide_price',
                    'is_photographer_available',
                    'photographer_price',
                    'activity_days',
                    'activity_times_start',
                    'activity_times_end',
                    'privacy_policy_en',
                    'privacy_policy_ar',
                    'cancel_policy_en',
                    'cancel_policy_ar',
                    'activity_single_dates'
                )
                ->where('user_id', auth()->id())
                ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
                ->where('activities.id', $id)
                ->firstOrFail();

            // Calculate average rating
            $ratings = $activity->rate->pluck('rating');  // Assuming 'rate' has a 'rating' field
            $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0;  // Calculate average or default to 0
            // $activity->average_rating = number_format($averageRating, 1, '.', ','); // Add average rating to the activity response add show only one number after point
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            $activity->rate = $activity->rate->map(function ($rate) {
                // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
                // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
                $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();  // Add time_ago
                $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
                return $rate;
            });

            // Process available_times for the activity
            $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
            $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
            $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
            $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];
            // dd($activitySingleDates);
            $availableTimes = [];

            foreach ($activitySingleDates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);
                    $dayName = strtolower($date->format('l'));

                    // ابحث عن اليوم داخل activityDays
                    foreach ($activityDays as $index => $day) {
                        if (strtolower(trim($day)) === $dayName) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$index] ?? null,
                                'end_time' => $activityTimesEnd[$index] ?? null,
                            ];
                            break;  // وجدنا اليوم، لا داعي لإكمال اللوب
                        }
                    }
                } catch (\Exception $e) {
                    // تجاهل الأخطاء الناتجة عن تواريخ غير صحيحة
                    continue;
                }
            }

            $activity->available_times = $availableTimes;

            // Fetch related data in bulk to optimize the number of queries
            $activityIds = [$activity->id];  // Since this is a single activity, we only need its ID

            $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
            $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
            $toolIds = $activityTools->pluck('commercial_tool_id');  // Extract all IDs into an array
            // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
            // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

            // Attach related data to the activity
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();  // Ensure it's an indexed array

            $tools = CommercialTool::whereIn('id', $toolIds)->get();
            $tools->transform(function ($tool) {
                // Convert tool_attributes to an indexed array
                // $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
                //     $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values();  // Ensure values is an indexed array
                //     return $att;
                // })->values();  // Ensure tool_attributes is an indexed array

                // Convert tool_images to an indexed array
                $tool->tool_images = $tool->toolImages->map(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                })->values();  // Ensure tool_images is an indexed array

                return $tool;
            });

            $activity->tools = $tools->values();  // Ensure tools is an indexed array

            // Attach images with full paths
            if ($activity->relationLoaded('activityImages')) {
                $activity->activityImages->transform(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                });
            }

            return response()->json([
                'data' => $activity
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip not found',
                'message_ar' => 'لم يتم العثور على الرحلة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'message_ar' => 'حدث خطأ غير متوقع'
            ], 500);
        }
    }

    // Update Activity Data
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        $activity = $this->service->updateActivity($activity, $request->only([
            'title_en',
            'title_ar',
            'description_ar',
            'description_en',
            'location',
            // 'time',
            // 'duration',
            'price',
            'capacity',
            'status_id',
        ]));

        // return response()->json($activity);

        $activity = Activity::with('activityImages')
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'description_en',
                'description_ar',
                'city_name_en',
                'city_name_ar',
                'country_name_en',
                'country_name_ar',
                'duration',
                'plan_activity',
                'start_date',
                'price',
                'activities.spoken_lang',
                'capacity',
                'status',
                'lat',
                'long',
                'is_tourguideable',
                'tourguide_price',
                'is_photographer_available',
                'photographer_price',
                'activity_days',
                'activity_times_start',
                'activity_times_end',
                'privacy_policy_en',
                'privacy_policy_ar',
                'cancel_policy_en',
                'cancel_policy_ar',
            )
            ->where('user_id', auth()->id())
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->where('activities.id', $activity->id)
            ->firstOrFail();

        // Calculate average rating
        $ratings = $activity->rate->pluck('rating');  // Assuming 'rate' has a 'rating' field
        $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0;  // Calculate average or default to 0
        // $activity->average_rating = number_format($averageRating, 1, '.', ','); // Add average rating to the activity response add show only one number after point
        $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

        $activity->rate = $activity->rate->map(function ($rate) {
            // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
            // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
            $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();  // Add time_ago
            $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
            return $rate;
        });

        // Process available_times for the activity
        $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
        $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

        $availableTimes = [];
        foreach ($activityDays as $index => $day) {
            if (isset($activityTimes[$index])) {
                $date = Carbon::parse($day);  // Parse the date to get the day name
                $dayName = $date->format('l');  // Get the full day name (e.g., "Monday")
                $availableTimes[] = [
                    'day_name' => $dayName,  // Include only the day name
                    'time' => $activityTimes[$index],  // Include the time
                ];
            }
        }
        $activity->available_times = $availableTimes;

        // Fetch related data in bulk to optimize the number of queries
        $activityIds = [$activity->id];  // Since this is a single activity, we only need its ID

        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = Tool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to the activity
        $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();  // Ensure it's an indexed array

        $tools = $activityTools->where('activity_id', $activity->id);
        $tools->transform(function ($tool) {
            // Convert tool_attributes to an indexed array
            // $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
            //     $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values();  // Ensure values is an indexed array
            //     return $att;
            // })->values();  // Ensure tool_attributes is an indexed array

            // Convert tool_images to an indexed array
            $tool->tool_images = $tool->toolImages->map(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            })->values();  // Ensure tool_images is an indexed array

            return $tool;
        });

        $activity->tools = $tools->values();  // Ensure tools is an indexed array

        // Attach images with full paths
        if ($activity->relationLoaded('activityImages')) {
            $activity->activityImages->transform(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            });
        }

        if ($request->status_id == 3) {
            $all_booking_users = WaitingList::where('activity_id', $activity->id)->pluck('user_id')->toArray();
            WaitingList::where('activity_id', $activity->id)
                ->whereIn('user_id', $all_booking_users)  // If you want to update specific users
                ->update(['status' => 'accepted']);

            // dd($all_booking_users);

            $notificationData = [
                'title_en' => 'Trip is Accepted',
                'title_ar' => 'تم تفعيل هذا الرحلة',
                'body_en' => 'This trip "' . $activity->title_en . '" with id = ' . $activity->id . ' is Accepted',
                'body_ar' => "تم تفعيل هذا الرحلة '{$activity->title_ar}' مع المعرف = {$activity->id}",
                'type' => 'reservation_accepted',
                'book_id' => $booking->id,
                'action' => 'update_activity',
                'send_to_all' => false,
            ];

            // dd($notificationData);
            // Call the push_book method from the notification controller
            $pushController = new NotificationController();  // Create an instance of the notification controller
            // foreach ($all_booking_users as $all_users) {
            $pushController->push_book_many(new PushNotificationRequest($notificationData), $all_booking_users);
            // }
        }

        $user_email = User::where('id', $user->id)->value('email');

        if ($user_email) {
            try {
                // Mail::to($user_email)->send(new ActivityStatusMail(
                //     activityTitle: $activity->title_en,
                //     status: 'Accepted'
                // ));

                sendViewEmail($user_email, __('Trip is Accepted'), 'emails.activity-status', [
                    'activityTitle' => $activity->title_en,
                    'status' => 'Accepted'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send activity status email:2222222222222222222222222222222222222222 ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Trip updated successfully',
            'message_ar' => 'تم تحديث الرحلة بنجاح',
            'data' => $activity
        ], 200);
    }

    // Delete Activity
    public function destroy($id)
    {
        $hasBookings = Booking::where('activity_id', $id)->exists();
        if (!$hasBookings) {
            // try {
            $activity = Activity::findOrFail($id);
            if ($activity) {
                // Access related activity plans
                $activityPlans = ActivityPlan::where('activity_id', $id);  // Correct way to access the relationship

                // Delete activity plans
                foreach ($activityPlans as $plan) {
                    $plan->delete();
                }

                // Access related tools
                $tools = Tool::where('activity_id', $id);  // Correct way to access the relationship

                // Delete tools
                foreach ($tools as $tool) {
                    $tool->delete();
                }
            }

            // Delete the activity itself
            $activity->delete();

            return response()->json([
                // 'message' => __('SUCCESS'),
                'message' => "Trip and it's plans and tools has been deleted successfully",
                'message_ar' => 'تم حذف الرحلة وخططها وأدواتها بنجاح',
                'is_deleted' => true,
            ], 200);
            // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            //     return response()->json([
            //         'message' => 'Trip not found',
            //         'message_ar' => 'لم يتم العثور على الرحلة',
            //     ], 404);
            // } catch (\Exception $e) {
            //     return response()->json([
            //         'message' => 'An error occurred while processing your request.',
            //         'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
            //         'error' => $e->getMessage(),
            //     ], 500);
            // }
        } else {
            return response()->json([
                // 'message' => __('SUCCESS'),
                'message' => 'Trip has bookings',
                'message_ar' => 'هذه الرحلة لديها حجوزات',
            ], 200);
        }
    }

    public function showBookingDetails($id)
    {
        $booking = Booking::with([
            'activity.activityType',
            'activity.serviceprovider',
            'activity.activityImages',
            'booking_status',
            'customer',
            // 'customer.userImage',
            'activity.rate.user'
        ])
            // ->where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Not found',
                'message_ar' => 'البيانات ليست موجودة',
            ], 404);
        }

        $activity = $booking->activity;

        if ($activity) {
            // === Ratings ===
            $ratings = $activity->rate ? $activity->rate->pluck('rating') : collect();
            $activity->average_rating = $ratings->isNotEmpty()
                ? number_format($ratings->avg(), 4, '.', ',')
                : 0;

            $activity->rate = $activity->rate ? $activity->rate->map(function ($rate) {
                $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans();
                $rate->time_ago_ar = Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
                return $rate;
            }) : collect();

            // === Available times ===
            $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
            $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
            $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
            $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];

            $availableTimes = [];
            foreach ($activitySingleDates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);
                    $dayName = strtolower($date->format('l'));

                    foreach ($activityDays as $index => $day) {
                        if (strtolower(trim($day)) === $dayName) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$index] ?? null,
                                'end_time' => $activityTimesEnd[$index] ?? null,
                            ];
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            $activity->available_times = $availableTimes;

            // === Attach activity plans ===
            $activity->activity_plans = ActivityPlan::where('activity_id', $activity->id)->get();

            // === Fix activity images ===
            if ($activity->relationLoaded('activityImages')) {
                $activity->activityImages->transform(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                });
            }

            // === Customer image ===
            if ($booking->customer && $booking->customer->relationLoaded('userImage')) {
                $booking->customer->user_image_url = $booking->customer->user_image_url;  // accessor
            }

            // === Tools ===
            $toolIds = !empty($booking->tool_id) ? explode(',', $booking->tool_id) : [];
            $toolCapacities = !empty($booking->tool_capacity) ? explode(',', $booking->tool_capacity) : [];

            $tools = CommercialTool::with('toolImages')
                ->whereIn('id', $toolIds)
                ->get();

            $selected_tools = $tools->map(function ($tool) use ($toolIds, $toolCapacities) {
                $index = array_search($tool->id, $toolIds);
                $tool->capacity = $toolCapacities[$index] ?? 1;

                $tool->tool_images = $tool->toolImages->map(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                })->values();

                return $tool;
            });

            $booking->selected_tools = $selected_tools;

            // Attach provider image if exists
            if ($booking->customer->live_photo) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($booking->customer->live_photo)->name;
                // Retrieve the user's profile image if available
                $profileImage = null;
                // Assuming the image is stored in 'storage/app/public/activities'
                $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
                $booking->customer->live_photo = $profileImage;
                $booking->customer->profileImage = $booking->customer->live_photo;
            } else {
                $profileImage = null;
                $booking->customer->live_photo = $profileImage;
                $booking->customer->profileImage = $booking->customer->live_photo;
            }

            if ($booking->activity->serviceprovider->user_types_id == 3) {  // Indvidual
                if ($booking->activity->serviceprovider->live_photo) {
                    // $path = DB::table('files')->find($user->live_photo)->path;
                    $image_name = DB::table('files')->find($booking->activity->serviceprovider->live_photo)->name;
                    // Retrieve the user's profile image if available
                    $profileImage = null;
                    // Assuming the image is stored in 'storage/app/public/activities'
                    $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
                    $booking->activity->serviceprovider->live_photo = $profileImage;
                    $booking->activity->serviceprovider->profileImage = $profileImage;
                } else {
                    $profileImage = null;
                    $booking->activity->serviceprovider->live_photo = $profileImage;
                    $booking->activity->serviceprovider->profileImage = $profileImage;
                }
            } else if ($booking->activity->serviceprovider->user_types_id == 2) {  // Company
                if ($booking->activity->serviceprovider->company_logo) {
                    // $path = DB::table('files')->find($user->live_photo)->path;
                    $image_name2 = DB::table('files')->find($booking->activity->serviceprovider->company_logo)->name;
                    // Retrieve the user's company_logo if available
                    $company_logo = null;
                    // Assuming the image is stored in 'storage/'
                    $company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
                    $booking->activity->serviceprovider->company_logo = $company_logo;
                    $booking->activity->serviceprovider->profileImage = $company_logo;
                } else {
                    $company_logo = null;
                    $booking->activity->serviceprovider->company_logo = $company_logo;
                    $booking->activity->serviceprovider->profileImage = $company_logo;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trip Details',
            'message_ar' => 'تفاصيل الرحلة',
            'data' => $booking,
        ]);
    }

    // public function order_details($order_id)
    // {
    //     $order = Order::with(['user', 'orderItems', 'orderItems.tool', 'orderItems.tool.toolImages'])
    //         // ->where('user_id', auth()->id())
    //         ->find($order_id);

    //     if (!$order) {
    //         return response()->json([
    //             'message' => 'Order not found',
    //             'message_ar' => 'الطلب غير موجود',
    //         ], 404);
    //     }

    //     // Transform order items
    //     $order->orderItems->transform(function ($item) {
    //         // ✅ Calculate sub_total for each item
    //         $item->sub_total = $item->quantity * $item->price;

    //         if ($item->tool && $item->tool->toolImages) {
    //             $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
    //                 if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
    //                     $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
    //                 }
    //                 return $image;
    //             });
    //         }

    //         return $item;
    //     });

    //     return response()->json([
    //         'message' => true,
    //         'data' => $order
    //     ], 200);
    // }
    
    
    public function order_details($order_id)
{
    $order = Order::with([
        'user',
        'orderItems',
        'orderItems.tool',
        'orderItems.tool.toolImages',
    ])->find($order_id);

    if (!$order) {
        return response()->json([
            'message'    => 'Order not found',
            'message_ar' => 'الطلب غير موجود',
        ], 404);
    }

    // ── Filter: provider's tools only ────────────────────────
    $filteredItems = $order->orderItems
        ->filter(fn($item) => $item->tool && $item->tool->user_id === auth()->id())
        ->values()
        ->transform(function ($item) {
            $item->sub_total = $item->quantity * $item->price;

            if ($item->tool?->toolImages) {
                $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
                    if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    }
                    return $image;
                });
                // ✅ hide the raw relation to keep response clean
                unset($item->tool->toolImages);
            }

            return $item;
        });

    if ($filteredItems->isEmpty()) {
        return response()->json([
            'message'    => 'No tools found for this provider in this order',
            'message_ar' => 'لا توجد أدوات لهذا المزود في هذا الطلب',
        ], 404);
    }

    $provider_total = $filteredItems->sum('sub_total');

    // ✅ unset the relation so it doesn't appear in response
    unset($order->orderItems);

    return response()->json([
        'message' => true,
        'data'    => [
            'order_id'           => $order->id,
            'status'             => $order->status,
            'is_paid'            => $order->is_paid,
            'total'     => (string)$provider_total,
            'location'           => $order->location,
            'additional_details' => $order->additional_details,
            'created_at'         => $order->created_at,
            'customer'           => $order->user,
            'order_items'        => $filteredItems,     // ✅ filtered & one key only
        ],
    ], 200);
}
}
