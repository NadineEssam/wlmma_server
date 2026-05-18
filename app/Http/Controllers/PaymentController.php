<?php

namespace App\Http\Controllers;

use App\Http\Requests\PushNotificationRequest;
use App\Mail\BookingNotificationMail;
use App\Models\Activity;
use App\Models\BillingInformation;
use App\Models\Booking;
use App\Models\Cart;
use App\Models\CommercialTool;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CartService;
use App\Services\HyperPayService;
use App\Services\InvoiceService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPMailer\PHPMailer\PHPMailer;

require base_path('vendor/phpmailer/phpmailer/src/PHPMailer.php');
require base_path('vendor/phpmailer/phpmailer/src/SMTP.php');
require base_path('vendor/phpmailer/phpmailer/src/Exception.php');

class PaymentController extends Controller
{
    protected $cartService;

    protected $hyperPayService;

    protected $invoiceService;

    public function __construct(HyperPayService $hyperPayService, InvoiceService $invoiceService, CartService $cartService)
    {
        $this->hyperPayService = $hyperPayService;
        $this->invoiceService = $invoiceService;
        $this->cartService = $cartService;
    }

    // Step 1: Create Checkout & Save Card
    public function createCheckout(Request $request)
    {
        $user = Auth::user();  // Get authenticated user

        // Validate incoming request
        $request->validate([
            'email' => 'required|email',
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string|size:2',
            'postcode' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'cart_id' => 'nullable|numeric|exists:carts,id',
            'book_id' => 'nullable|numeric|exists:bookings,id',
            'payment_method' => 'required|in:visa,master,mada,applepay',
            'amount' => 'nullable|numeric|min:1',
            'partial_wallet' => 'nullable',
            /* FOR ORDER */
            // 'lat' => 'nullable|numeric',
            // 'long' => 'nullable|numeric',
            // 'additional_details' => 'nullable|string',
            // 'location' => 'nullable|string',
            // 'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
            // 'tools.*.quantity' => 'required|integer|min:1',
            /* FOR ORDER */
        ]);
        $walletService = new WalletService;

        $allcartbook_total_price = 0;
        if (isset($request->cart_id)) {
            /* FOR ORDER */
            // Get cart with its items
            // $cart = Cart::with('cartItems')->find($request->cart_id);

            // $order = Order::create([
            //     'user_id' => auth()->id(),
            //     'total' => 0,  // will update this later after adding items
            //     // 'lat' => $request->lat,
            //     // 'long' => $request->long,
            //     'shortNationalAddress' => $request->shortNationalAddress,
            //     'location' => $request->location,
            //     'additional_details' => $request->additional_details,
            //     'status' => 'pending',
            // ]);

            // // // Use tools from request if provided, otherwise fetch from cart
            // if ($request->tools && count($request->tools) > 0) {
            //     // Tools provided in request
            //     foreach ($request->tools as $item) {
            //         $tool = CommercialTool::find($item['tool_id']);
            //         if (!$tool)
            //             continue;

            //         $orderItem = OrderItem::where('order_id', $order->id)
            //             ->where('tool_id', $item['tool_id'])
            //             ->first();

            //         if ($orderItem) {
            //             $orderItem->quantity += $item['quantity'];
            //             $orderItem->save();
            //         } else {
            //             OrderItem::create([
            //                 'order_id' => $order->id,
            //                 'price' => $tool->price,
            //                 'tool_id' => $item['tool_id'],
            //                 'quantity' => $item['quantity'],
            //             ]);
            //         }
            //     }
            // } else {
            //     // Fetch tools from cart items
            //     foreach ($cart->cartItems ?? [] as $cartItem) {
            //         $tool = CommercialTool::find($cartItem->tool_id);
            //         if (!$tool)
            //             continue;

            //         OrderItem::create([
            //             'order_id' => $order->id,
            //             'price' => $tool->price,
            //             'tool_id' => $cartItem->tool_id,
            //             'quantity' => $cartItem->quantity,
            //         ]);
            //     }
            // }

            // // // Recalculate total price from all order items
            // $total_price = 0;
            // $all_order_items = OrderItem::where('order_id', $order->id)->get();

            // foreach ($all_order_items as $item) {
            //     $tool = CommercialTool::find($item->tool_id);
            //     $total_price += $tool->price * $item->quantity;
            // }

            // // Update the order with the correct total
            // $order->update([
            //     'old_price' => $cart->old_price ?? 0,
            //     'total' => $total_price,
            // ]);
            
            
             // ✅ Create Order here with pending status
    $order = Order::create([
        'user_id'            => auth()->id(),
        'total'              => 0,
        'old_price'          => $cart->old_price ?? 0,
        'location'           => $request->location,
        'additional_details' => $request->additional_details,
        'status'             => 'pending',
        'is_paid'            => false,
    ]);
    
   

    // Create order items from request tools or cart
    if ($request->filled('tools')) {
        foreach ($request->tools as $item) {
            $tool = CommercialTool::find($item['tool_id']);
            if (!$tool) continue;
            OrderItem::create([
                'order_id' => $order->id,
                'tool_id'  => $item['tool_id'],
                'quantity' => $item['quantity'],
                'price'    => $tool->price,
            ]);
        }
    } else {
        foreach ($cart->cartItems as $cartItem) {
            $tool = CommercialTool::find($cartItem->tool_id);
            if (!$tool) continue;
            OrderItem::create([
                'order_id' => $order->id,
                'tool_id'  => $cartItem->tool_id,
                'quantity' => $cartItem->quantity,
                'price'    => $tool->price,
            ]);
        }
    }

    // Recalculate total
    $total = OrderItem::where('order_id', $order->id)
        ->get()
        ->sum(fn($i) => $i->price * $i->quantity);

    $order->update(['total' => $total]);

            
            
            /* FOR ORDER */

            if ($request->partial_wallet == 1 && $request->amount) {
                $allcartbook_total_price = $request->amount;  // Deduct from gateway

                // Total Price of Cart
                $allcart_total_price = Cart::where('user_id', auth()->id())
                    ->where('id', $request->cart_id)
                    ->first()
                    ->total_price;

                // Deduct from wallet
                // $deucted_amount_from_wallet = $allcart_total_price - $allcartbook_total_price;

                // $walletService->debit($user->id, $request->book_id, $request->cart_id, $deucted_amount_from_wallet, null, 'Cart fully paid from wallet');
            } else {
                $allcartbook_total_price = Cart::where('user_id', auth()->id())
                    ->where('id', $request->cart_id)
                    ->first()
                    ->total_price;
                // dd($allcartbook_total_price);
            }

            // $this->cartService->clearCart($request->cart_id);
        } elseif (isset($request->book_id)) {
            if ($request->partial_wallet == 1 && $request->amount) {
                $allcartbook_total_price = $request->amount;  // Deduct from gateway

                // Total Price of Booking
                $allbook_total_price = Booking::where('user_id', auth()->id())
                    ->where('id', $request->book_id)
                    ->first()
                    ->total_price;

                // Deduct from wallet
                // $deucted_amount_from_wallet = $allbook_total_price - $allcartbook_total_price;

                // $walletService->debit($user->id, $request->book_id, $request->cart_id, $deucted_amount_from_wallet, null, 'Booking fully paid from wallet');

                // $booking_data = Booking::where('id', $request->book_id)->update(['status_id' => 5, 'is_paied' => 1]);
            } else {
                $allcartbook_total_price = Booking::where('user_id', auth()->id())
                    ->where('id', $request->book_id)
                    ->first()
                    ->total_price;
                // dd($allcartbook_total_price);
                // $booking_data = Booking::where('id', $request->book_id)->update(['status_id' => 5, 'is_paied' => 1]);
            }
        }

        $total_price = $allcartbook_total_price;
        // Pass all data including user ID
        $response = $this->hyperPayService->createCheckout(
            $user->id,  // Pass user ID
            $request->email,
            $request->street,
            $request->city,
            $request->state,
            $request->country,
            $request->postcode,
            $request->first_name,
            $request->last_name,
            $total_price,
            $request->cart_id,
            $request->book_id,
            $request->payment_method,
            $order->id ?? null
        );

        $user->update([
            'name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'street' => $request->street,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postcode' => $request->postcode,
        ]);

        // dd($response['entity_id']);

        // dd($response);
        return response()->json($response);
    }

    // Step 2: PAY
    public function form($CheckoutID)
    {
        return view('hyper.index', compact('CheckoutID'));
    }

    public function applePay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'token' => 'required|array',
        ]);

        $response = $this->hyperPayService->payWithApplePay(
            $request->amount,
            $request->token
        );

        return response()->json([
            'success' => str_starts_with($response['result']['code'] ?? '', '000'),
            'data' => $response,
        ]);
    }

    public function verify(Request $request)
    {
        try {
            // Validation
            $validated = $request->validate([
                'checkoutId' => 'required|string',
                'partial_wallet' => 'nullable|boolean',
                'retry_count' => 'nullable|integer|min:0|max:10',  // ✨ NEW: عدد المحاولات
            ]);

            $retryCount = $validated['retry_count'] ?? 0;

            Log::info('Apple Pay Verification Started', [
                'checkout_id' => $validated['checkoutId'],
                'user_id' => Auth::id(),
                'retry_count' => $retryCount,
            ]);

            // Get payment status from HyperPay
            $response = $this->hyperPayService->getPaymentStatus(
                $validated['checkoutId'],
                'applepay'
            );

            // Find billing information
            $billing = BillingInformation::where('payment_checkout_id', $validated['checkoutId'])->first();

            if (!$billing) {
                Log::error('Billing information not found for Apple Pay', [
                    'checkout_id' => $validated['checkoutId']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Billing information not found',
                ], 404);
            }

            // Verify it's actually an Apple Pay transaction
            if (!in_array(strtolower($billing->payment_method), ['apple', 'applepay'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This is not an Apple Pay transaction',
                ], 400);
            }

            // Extract result codes
            $code = $response['result']['code'] ?? null;
            $description = $response['result']['description'] ?? null;

            // ✨ NEW: Check if pending
            $isPending = $this->isPaymentPending($code);
            $isSuccessful = $this->isPaymentSuccessful($code);

            // Update billing information with payment result
            $billing_information->update([
                'payment_status' => $code,
                'describtion' => $description,
                'registration_id' => $response['registrationId'] ?? null,
                'payment_id' => $response['id'] ?? null,
            ]);

            Log::info('Apple Pay Status Retrieved', [
                'checkout_id' => $validated['checkoutId'],
                'code' => $code,
                'description' => $description,
                'is_pending' => $isPending,
                'is_successful' => $isSuccessful,
                'retry_count' => $retryCount,
            ]);

            // ✨ NEW: Handle Pending Status
            if ($isPending) {
                // حساب الوقت المناسب للـ retry
                $retryAfter = min(2 * ($retryCount + 1), 10);  // من 2 ثانية لـ 10 ثواني

                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Transaction is still processing. Please wait and retry.',
                    'data' => [
                        'checkout_id' => $validated['checkoutId'],
                        'code' => $code,
                        'description' => $description,
                        'retry_count' => $retryCount,
                        'retry_after_seconds' => $retryAfter,
                        'max_retries' => 10,
                    ],
                    'instructions' => [
                        'ar' => 'الدفع قيد المعالجة. يرجى الانتظار والمحاولة مرة أخرى بعد ' . $retryAfter . ' ثانية.',
                        'en' => 'Payment is being processed. Please wait and retry after ' . $retryAfter . ' seconds.',
                    ],
                ], 202);  // 202 Accepted - Processing
            }

            // Process successful payment
            if ($isSuccessful) {
                DB::beginTransaction();
                try {
                    $this->processSuccessfulPayment($billing, $request, $response);
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Apple Pay payment completed successfully',
                        'data' => [
                            'checkout_id' => $validated['checkoutId'],
                            'code' => $code,
                            'description' => $description,
                            'amount' => $billing->amount,
                            'currency' => 'SAR',
                            'payment_method' => 'Apple Pay',
                            'zatca' => $this->getZatcaData($billing),
                        ],
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            // Payment failed
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'Apple Pay payment failed',
                'data' => [
                    'code' => $code,
                    'description' => $description,
                    'retry_allowed' => false,
                ],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Apple Pay Verification Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during Apple Pay verification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Step 3: Get Payment Status & Store Token
    // public function getPaymentStatus(Request $request)
    // {
    //     $request->validate([
    //         'partial_wallet' => 'nullable',
    //         'checkoutId' => 'required|string',
    //         'payment_method' => 'required|in:visa,master,mada,applepay',
    //         /* FOR ORDER */
    //         'additional_details' => 'nullable|string',
    //         'location' => 'nullable|string',
    //         'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
    //         'tools.*.quantity' => 'required|integer|min:1',
    //         /* FOR ORDER */
    //     ]);
    //     // dd($request->all());
    //     $response = $this->hyperPayService->getPaymentStatus(
    //         $request->checkoutId,
    //         $request->payment_method
    //     );

    //     $billing_information = BillingInformation::where('payment_checkout_id', $request->checkoutId)->first();

    //     $code = $response['result']['code'] ?? null;
    //     $description = $response['result']['description'] ?? null;

    //     // Check if payment was successful (HyperPay success codes start with '000.')
    //     $isPaymentSuccessful = $code && str_starts_with($code, '000.');

    //     // Update billing information with payment result
    //     $billing_information->update([
    //         'payment_status' => $code,
    //         'describtion' => $description,
    //         'registration_id' => $response['registrationId'] ?? null,
    //         'payment_id' => $response['id'] ?? null,
    //     ]);

    //     // Log payment result for debugging
    //     Log::info('HyperPay Payment Result', [
    //         'checkout_id' => $request->checkoutId,
    //         'payment_id' => $response['id'],
    //         'code' => $code,
    //         'description' => $description,
    //         'is_successful' => $isPaymentSuccessful,
    //         'book_id' => $billing_information->book_id,
    //     ]);

    //     Log::info('Codexxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx => ' . $code);
    //     Log::info('descriptionxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx => ' . $description);

    //     if ($isPaymentSuccessful) {
    //         $user = Auth::user();
    //         if (!empty($response['registrationId'])) {
    //             $user->update(['registration_id' => $response['registrationId']]);
    //         }

    //         $amount = $billing_information->amount;
    //         $walletService = new WalletService;
    //         $booking_data = Booking::find($billing_information->book_id);
    //         $activity_id = $booking_data->activity_id ?? null;
    //         // $activity_data = Activity::find($activity_id);
    //         $activity_data = Activity::with('activityImages', 'activityType', 'serviceprovider')
    //             ->select(
    //                 'activities.id',
    //                 'activities.user_id',
    //                 'activity_type_id',
    //                 'title_en',
    //                 'title_ar',
    //                 'description_en',
    //                 'description_ar',
    //                 'city_name_en',
    //                 'city_name_ar',
    //                 'country_name_en',
    //                 'country_name_ar',
    //                 'duration',
    //                 'plan_activity',
    //                 'start_date',
    //                 'price',
    //                 'activities.spoken_lang',
    //                 'bookings.capacity',
    //                 // 'status',
    //                 'lat',
    //                 'long',
    //                 'is_tourguideable',
    //                 'tourguide_price',
    //                 'is_photographer_available',
    //                 'photographer_price',
    //                 'activity_days',
    //                 'activity_times_start',
    //                 'activity_times_end',
    //                 'privacy_policy_en',
    //                 'privacy_policy_ar',
    //                 'cancel_policy_en',
    //                 'cancel_policy_ar',
    //                 'activity_single_dates'
    //             )
    //             // ->where('user_id', auth()->id())
    //             ->join('bookings', 'activities.id', '=', 'bookings.activity_id')
    //             // ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
    //             // ->where('activities.id', $id)
    //             ->where('bookings.id', $billing_information->book_id)
    //             ->first();

    //         Log::info('$activity_data => ' . $activity_data);

    //         $allcartbook_total_price = $amount;

    //         if (!empty($billing_information->book_id)) {
    //             if ($request->partial_wallet == 1 && $amount) {
    //                 // Deduct from gateway

    //                 // Total Price of Booking
    //                 $allbook_total_price = Booking::where('user_id', auth()->id())
    //                     ->where('id', $billing_information->book_id)
    //                     ->first()
    //                     ->total_price;

    //                 // Deduct from wallet
    //                 $deucted_amount_from_wallet = $allbook_total_price - $allcartbook_total_price;

    //                 $walletService->debit($user->id, $billing_information->book_id, null, $deucted_amount_from_wallet, null, 'Booking fully paid from wallet');
    //             }

    //             // Mark booking as paid for all successful payments
    //             if (($code == '000.100.110' || $code == '000.100.101' || $code == '000.000.000') && $booking_data) {
    //                 $booking_data->update([
    //                     'status_id' => 5,
    //                     'is_paied' => 1,
    //                 ]);
    //             }

    //             // ==========================================
    //             // ZATCA E-Invoice Generation for Booking
    //             // ==========================================

    //             if (($code == '000.100.110' || $code == '000.100.101' || $code == '000.000.000') && $booking_data) {
    //                 $this->generateZatcaInvoiceForBooking($booking_data);
    //             }
    //             // dd($activity_data);
    //             $notificationData = [
    //                 'title_en' => 'Booking Payment Completed',
    //                 'title_ar' => 'تم الدفع بنجاح',
    //                 'body_en' => "A trip $activity_data->title_en has been paid .",
    //                 'body_ar' => "تم دفع الرحلة '{$activity_data->title_ar}' ",
    //                 'type' => 'payment_success',
    //                 'book_id' => $booking_data->id,
    //                 'action' => 'payment',
    //                 'send_to_all' => false,
    //             ];
    //             $pushController = new NotificationController;
    //             $pushController->push_welcome(new PushNotificationRequest($notificationData), $activity_data->user_id);  // OLD

    //             // $notificationData['user_id'] = $activity_data->user_id;

    //             // $response = $pushController->push_welcome(
    //             //     new PushNotificationRequest($notificationData)
    //             // );

    //             $user_email = User::where('id', $activity_data->user_id)->value('email');  // provider email
    //             $user_name = User::where('id', $activity_data->user_id)->value('name');  // provider name

    //             if ($user_email) {
    //                 try {
    //                     // Mail::to($user_email)->send(new BookingNotificationMail(
    //                     //     customerName: $user_name ?? 'Valued Customer',
    //                     //     activityTitle: $activity_data->title_en,
    //                     //     bookingStatus: 'paid',
    //                     //     notificationType: 'payment_completed'
    //                     // ));

    //                     $tomemail = $user->email;

    //                     sendViewEmail($tomemail, __('Booking Details'), 'emails.booking-notification', [
    //                         'customerName' => $tomemail ?? 'Valued Customer',
    //                         'activityTitle' => $activity_data->title_en,
    //                         'bookingStatus' => 'paid',
    //                         'notificationType' => 'payment_completed',
    //                     ]);

    //                     Log::info('Email => ' . $tomemail);
    //                     $mail = new PHPMailer(true);
    //                     $mail->isSMTP();
    //                     $mail->Host = 'notifications.wlmma.com';
    //                     $mail->SMTPAuth = true;
    //                     $mail->Username = env('MAIL_USERNAME');
    //                     $mail->Password = env('MAIL_PASSWORD');
    //                     // $mail->Password   = "I,Lrs63ACr~[";
    //                     $mail->SMTPSecure = 'ssl';
    //                     $mail->Port = 465;

    //                     $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
    //                     $mail->addAddress($tomemail);

    //                     $mail->isHTML(true);
    //                     $mail->Subject = 'Booking Details';

    //                     // $templateUrl = 'https://wlmma.com/public/EmailTemplate-Wlmma/tool.php';
    //                     // $html = file_get_contents($templateUrl);
    //                     if (!$booking_data) {
    //                         Log::warning('No BOOKING found for user_id: ' . auth()->id());

    //                         return response()->json(['message' => 'No BOOKING found'], 404);
    //                     }
    //                     $title = $activity_data->title_en ?? '-';
    //                     $city = $activity_data->city_name_en ?? '-';
    //                     $country = $activity_data->country_name_en ?? '-';
    //                     $guests = $booking_data->capacity ?? 0;
    //                     $price = number_format($activity_data->price ?? 0, 2);
    //                     $total = number_format($activity_data->total_price ?? 0, 2);

    //                     $bookingDate = $booking_data->date
    //                         ? \Carbon\Carbon::parse($booking_data->date)->format('d F Y')
    //                         : '-';

    //                     $startTime = $booking_data->time
    //                         ? \Carbon\Carbon::parse($booking_data->time)->format('g:i A')
    //                         : '-';

    //                     $bookedAt = \Carbon\Carbon::parse($booking_data->created_at)
    //                         ->format('Y, F d . g:i A');

    //                     if ($activity_data->relationLoaded('activityImages')) {
    //                         $activity_data->activityImages->transform(function ($img) {
    //                             $img->image_path = env('APP_URL') . 'storage/app/public/' . $img->image_path;

    //                             return $img;
    //                         });
    //                     }

    //                     $image = optional($activity_data->activityImages->first())->image_path
    //                         ?: 'https://wlmma.com/public/EmailTemplate-Wlmma/Image/App_Icon.png';

    //                     // Initialize variables
    //                     $costBreakdownHtml = '';
    //                     $additionalServicesHtml = '';

    //                     // Replace your existing HTML generation code with this:

    //                     // Prepare optional tools data
    //                     $optionalToolsHtml = '';

    //                     // Fetch activity tools - handle comma-separated tool_id
    //                     $toolIds = explode(',', $booking_data->tool_id);
    //                     $toolCapacities = explode(',', $booking_data->tool_capacity);
    //                     $activityTools = CommercialTool::whereIn('id', $toolIds)->get();

    //                     // Prepare optional tools data
    //                     $optionalToolsHtml = '';
    //                     if ($activityTools && $activityTools->count() > 0) {
    //                         $toolsCards = '';
    //                         $toolCount = 0;
    //                         foreach ($activityTools as $index => $tool) {
    //                             $toolName = $tool->name_en ?? '-';
    //                             $toolPrice = number_format($tool->price ?? 0, 2);
    //                             $toolQty = $toolCapacities[$index] ?? 0;

    //                             $toolImages = $tool
    //                                 ->toolImages
    //                                 ->map(fn($image) => env('APP_URL') . 'storage/app/public/' . $image->image_path)
    //                                 ->values()
    //                                 ->toArray();

    //                             $toolImage = !empty($toolImages)
    //                                 ? $toolImages[0]
    //                                 : 'https://wlmma.com/public/EmailTemplate-Wlmma/Image/App_Icon.png';

    //                             // Add closing row and start new row every 2 tools
    //                             if ($toolCount > 0 && $toolCount % 2 === 0) {
    //                                 $toolsCards .= '
    //                                     <td width="12%"></td>
    //                                 </tr>
    //                                 <tr style="margin-top: 12px;">';
    //                             }

    //                             $toolsCards .= '
    //                                     <td width="40%" style="vertical-align: top; padding-left: 8px; padding-top: 12px;">
    //                                         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                             style="background-color: #0D0D0D02; border: 1px solid #0D0D0D10; border-radius: 12px; text-align: center;">
    //                                             <tr>
    //                                                 <td style="padding: 12px 24px;">
    //                                                     <img src="' . $toolImage . '" alt="' . $toolName . '" width="80" height="80"
    //                                                         style="display: block; margin: 0 auto 8px auto;">
    //                                                     <p style="margin: 0 0 8px 0; font-size: 18px; color: #0D0D0D; max-width:260px; overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
    //                                                         ' . $toolName . '</p>
    //                                                     <p style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #FF5B4B;">
    //                                                         ريال ' . $toolPrice . '</p>
    //                                                     <p style="margin: 0; font-size: 20px; font-weight: 700; color: #0D0D0D;">
    //                                                         X' . $toolQty . '</p>
    //                                                 </td>
    //                                             </tr>
    //                                         </table>
    //                                     </td>';

    //                             // Add spacer column if not the last item and not at end of row
    //                             if ($toolCount % 2 === 0) {
    //                                 $toolsCards .= '<td width="4%"></td>';
    //                             }

    //                             $toolCount++;
    //                         }

    //                         // Fill remaining space if odd number of tools
    //                         if ($toolCount % 2 !== 0) {
    //                             $toolsCards .= '<td width="44%"></td>';
    //                         }

    //                         $optionalToolsHtml = '
    //                                 <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                     style="border-bottom: 1px dashed #0D0D0D10;">
    //                                     <tr>
    //                                         <td colspan="2" style="padding: 16px 0 8px 0;">
    //                                             <p style="margin: 0 0 12px 0; font-size: 16px; color: #0D0D0D70;">
    //                                                 Optional Tools</p>
    //                                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    //                                                 <tr>
    //                                                     ' . $toolsCards . '
    //                                                 </tr>
    //                                             </table>
    //                                         </td>
    //                                     </tr>
    //                                 </table>';
    //                     }

    //                     // Prepare additional services data
    //                     $book_total = $booking_data->total_price;
    //                     // Add photographer to cost breakdown (REMOVE the $additionalServicesHtml = '' line!)
    //                     if ($booking_data->photographer == 1) {
    //                         $additionalServices_price = $activity_data->photographer_price;
    //                         $serviceName = 'Photographer';
    //                         // DO NOT RESET: $additionalServicesHtml = ''; // ← REMOVE THIS LINE

    //                         $servicePrice = number_format($additionalServices_price ?? 0, 2);

    //                         $costBreakdownHtml .= '
    //                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                 style="border-bottom: 1px dashed #0D0D0D10;">
    //                                 <tr>
    //                                     <td align="left" style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">
    //                                         ' . $serviceName . '</td>
    //                                     <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                         ريال ' . $servicePrice . ' </td>
    //                                 </tr>
    //                             </table>';
    //                     }

    //                     // Add tour guide to cost breakdown (REMOVE the $additionalServicesHtml = '' line!)
    //                     if ($booking_data->tour_guide == 1) {
    //                         $additionalServices_price = $activity_data->tourguide_price;
    //                         $serviceName = 'Tour Guide';
    //                         // DO NOT RESET: $additionalServicesHtml = ''; // ← REMOVE THIS LINE

    //                         $servicePrice = number_format($additionalServices_price ?? 0, 2);

    //                         $costBreakdownHtml .= '
    //                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                 style="border-bottom: 1px dashed #0D0D0D10;">
    //                                 <tr>
    //                                     <td align="left" style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">
    //                                         ' . $serviceName . '</td>
    //                                     <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                         ريال ' . $servicePrice . ' </td>
    //                                 </tr>
    //                             </table>';
    //                     }

    //                     // if($booking_data->tour_guide == 1){
    //                     //     $additionalServices_price= $activity_data->tourguide_price;
    //                     //     // $book_total += $additionalServices_price;

    //                     //     $serviceName ="Tour Guide";
    //                     //     $additionalServicesHtml = '';
    //                     //     // if (isset($additionalServices_price) && is_array($additionalServices_price) && count($additionalServices_price) > 0) {
    //                     //         $servicesRows = '';

    //                     //             $servicePrice = number_format($additionalServices_price ?? 0, 2);
    //                     //             $serviceImage = 'https://wlmma.com/public/EmailTemplate-Wlmma/Image/avatar_1.png';

    //                     //             $servicesRows .= '
    //                     //             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                     //                 style="background-color: #0D0D0D02; border: 1px solid #0D0D0D10; border-radius: 999px; margin-bottom: 12px;">
    //                     //                 <tr>
    //                     //                     <td width="70" style="padding: 0;">
    //                     //                         <img src="'.$serviceImage.'" alt="'.$serviceName.'" width="70" height="70"
    //                     //                             style="display: block; border-radius: 50%;">
    //                     //                     </td>
    //                     //                     <td style="padding: 0 16px; font-size: 18px; color: #0D0D0D;">
    //                     //                         '.$serviceName.'</td>
    //                     //                     <td align="right" style="padding: 0 16px 0 0; font-size: 20px; font-weight: 700; color: #FF5B4B; white-space: nowrap;">
    //                     //                         ريال '.$additionalServices_price.'</td>
    //                     //                 </tr>
    //                     //             </table>';

    //                     //         $additionalServicesHtml = '
    //                     //         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                     //             style="border-bottom: 1px dashed #0D0D0D10;">
    //                     //             <tr>
    //                     //                 <td colspan="2" style="padding: 16px 0 8px 0;">
    //                     //                     <p style="margin: 0 0 12px 0; font-size: 16px; color: #0D0D0D70;">
    //                     //                         Additional Services</p>
    //                     //                     '.$servicesRows.'
    //                     //                 </td>
    //                     //             </tr>
    //                     //         </table>';
    //                     //     // }
    //                     // }

    //                     // Prepare booking cost breakdown

    //                     $subtotal = number_format($book_total ?? 0, 2);
    //                     $total = number_format($book_total ?? 0, 2);
    //                     // $discount = number_format($activity_data->discount ?? 0, 2);
    //                     // $promoCode = $activity_data->promo_code ?? '';

    //                     $costBreakdownHtml .= '
    //                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                 style="border-bottom: 1px dashed #0D0D0D10;">
    //                                 <tr>
    //                                     <td style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">Price</td>
    //                                     <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                         ريال ' . $price . '</td>
    //                                 </tr>
    //                             </table>';

    //                     if ($guests > 1) {
    //                         $costBreakdownHtml .= '
    //                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                 style="border-bottom: 1px dashed #0D0D0D10;">
    //                                 <tr>
    //                                     <td style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">Subtotal</td>
    //                                     <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                         <p style="font-size: 16px; font-weight: 400; color: #0d0d0db2; display: inline;">
    //                                             ' . $price . ' X ' . $guests . ' </p>
    //                                         <p style="font-size: 18px; font-weight: 500; color: #0D0D0D;display: inline;">
    //                                             ريال ' . $subtotal . ' </p>
    //                                     </td>
    //                                 </tr>
    //                             </table>';
    //                     }

    //                     // if ($discount > 0) {
    //                     //     $costBreakdownHtml .= '
    //                     //     <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                     //         style="border-bottom: 1px dashed #0D0D0D10;">
    //                     //         <tr>
    //                     //             <td align="left" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                     //                 <p style="font-size: 18px; font-weight: 500; color: #0d0d0db2; display: inline;">Discount </p>
    //                     //                 '.($promoCode ? '<p style="font-size: 16px; font-weight: 400; color: #FF5B4B;display: inline;">'.$promoCode.' </p>' : '').'
    //                     //             </td>
    //                     //             <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                     //                 ريال -'.$discount.' </td>
    //                     //         </tr>
    //                     //     </table>';
    //                     // }

    //                     // Add optional tools to cost breakdown

    //                     // Fetch activity tools
    //                     // $activityIds = [$booking_data->tool_id];
    //                     // Get the tool(s) for the booking
    //                     if ($activityTools && $activityTools->count() > 0) {
    //                         foreach ($activityTools as $index => $tool) {
    //                             $toolName = $tool->name_en ?? '-';
    //                             $toolPrice = number_format($tool->price ?? 0, 2);
    //                             $toolQty = $toolCapacities[$index] ?? 0;

    //                             $costBreakdownHtml .= '
    //                                 <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                     style="border-bottom: 1px dashed #0D0D0D10;">
    //                                     <tr>
    //                                         <td align="left" width="40%" style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">
    //                                             ' . $toolName . '</td>
    //                                         <td align="center" style="padding: 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                             X' . $toolQty . '</td>
    //                                         <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                             ريال ' . $toolPrice . ' </td>
    //                                     </tr>
    //                                 </table>';
    //                         }
    //                     }
    //                     // if (isset($activity_data->optional_tools) && is_array($activity_data->optional_tools)) {
    //                     //     foreach ($activity_data->optional_tools as $tool) {
    //                     //         $toolName = $tool['name'] ?? '-';
    //                     //         $toolPrice = number_format($tool['price'] ?? 0, 2);
    //                     //         $toolQty = $tool['quantity'] ?? 1;

    //                     //         $costBreakdownHtml .= '
    //                     //         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                     //             style="border-bottom: 1px dashed #0D0D0D10;">
    //                     //             <tr>
    //                     //                 <td align="left" width="40%" style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">
    //                     //                     '.$toolName.'</td>
    //                     //                 <td align="center" style="padding: 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                     //                     X'.$toolQty.'</td>
    //                     //                 <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                     //                     ريال '.$toolPrice.' </td>
    //                     //             </tr>
    //                     //         </table>';
    //                     //     }
    //                     // }

    //                     // Add additional services to cost breakdown

    //                     // Then your photographer block
    //                     if ($booking_data->photographer == 1) {
    //                         $additionalServices_price = $activity_data->photographer_price;
    //                         $serviceName = 'Photographer';

    //                         $servicePrice = number_format($additionalServices_price ?? 0, 2);
    //                         $serviceImage = 'https://wlmma.com/public/EmailTemplate-Wlmma/Image/avater_2.png';

    //                         $additionalServicesHtml .= '
    //                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                 style="border-bottom: 1px dashed #0D0D0D10;">
    //                                 <tr>
    //                                     <td width="70" style="padding: 0;">
    //                                         <img src="' . $serviceImage . '" alt="' . $serviceName . '"height="70"
    //                                                                                                     style="display: block; vertical-align: middle; margin-right: 8px;  border-radius: 50%;">
    //                                     </td>
    //                                     <td style="padding: 0 16px; font-size: 18px; color: #0D0D0D;">
    //                                         ' . $serviceName . '
    //                                     </td>
    //                                     <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                         ريال ' . $servicePrice . '
    //                                     </td>
    //                                 </tr>
    //                             </table>';
    //                     }

    //                     if ($booking_data->tour_guide == 1) {
    //                         $additionalServices_price = $activity_data->tourguide_price;
    //                         // $book_total += $additionalServices_price;

    //                         $serviceName = 'Tour Guide';

    //                         // if (isset($additionalServices_price) && is_array($additionalServices_price) && count($additionalServices_price) > 0) {
    //                         $servicesRows = '';

    //                         $servicePrice = number_format($additionalServices_price ?? 0, 2);
    //                         $serviceImage = 'https://wlmma.com/public/EmailTemplate-Wlmma/Image/avatar_1.png';

    //                         $costBreakdownHtml .= '
    //                                 <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                                     style="border-bottom: 1px dashed #0D0D0D10;">
    //                                     <tr>
    //                                         <td align="left" style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">
    //                                             ' . $serviceName . '</td>
    //                                         <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                                             ريال ' . $servicePrice . ' </td>
    //                                     </tr>
    //                                 </table>';

    //                         // }
    //                     }

    //                     // if (isset($activity_data->additional_services) && is_array($activity_data->additional_services)) {
    //                     //     foreach ($activity_data->additional_services as $service) {
    //                     //         $serviceName = $service['name'] ?? '-';
    //                     //         $servicePrice = number_format($service['price'] ?? 0, 2);

    //                     //         $costBreakdownHtml .= '
    //                     //         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
    //                     //             style="border-bottom: 1px dashed #0D0D0D10;">
    //                     //             <tr>
    //                     //                 <td align="left" style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">
    //                     //                     '.$serviceName.'</td>
    //                     //                 <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">
    //                     //                     ريال '.$servicePrice.' </td>
    //                     //             </tr>
    //                     //         </table>';
    //                     //     }
    //                     // }

    //                     // Rating display
    //                     // $rating = $activity_data->rating ?? 0; // POSTPONED
    //                     // $ratingHtml = $rating > 0 ? '
    //                     // <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 8px;">
    //                     //     <tr>
    //                     //         <td style="padding: 0;">
    //                     //             <span style="color: #FFD700; font-size: 20px;">⭐</span>
    //                     //         </td>
    //                     //         <td style="padding: 2px 0 0 4px;">
    //                     //             <span style="font-size: 16px; color: #0D0D0D; font-weight: 500;">'.$rating.'</span>
    //                     //         </td>
    //                     //     </tr>
    //                     // </table>' : '';

    //                     // Generate complete HTML
    //                     $html = '
    //                                 <!DOCTYPE html>
    //                                 <html lang="en" dir="ltr">
    //                                 <head>
    //                                     <meta charset="UTF-8">
    //                                     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    //                                     <title>Booking Details</title>
    //                                 </head>
    //                                 <body style="margin: 0; padding: 0; background-color: #FFFCFC; font-family: Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    //                                     <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFFCFC;">
    //                                         <tr>
    //                                             <td align="center" style="padding: 0;">
    //                                                 <!-- Header -->
    //                                                 <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 650px; background-color: #FFFCFC;">
    //                                                     <tr>
    //                                                         <td align="center" style="padding: 60px 20px 40px 20px;">
    //                                                             <img src="https://wlmma.com/public/EmailTemplate-Wlmma/Image/logo.png" alt="Logo" width="150" height="150" style="display: block; margin: 0 auto 16px auto; max-width: 150px; height: auto;">
    //                                                         </td>
    //                                                     </tr>
    //                                                 </table>

    //                                                 <!-- Main Content -->
    //                                                 <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 650px;">
    //                                                     <tr>
    //                                                         <td style="padding: 0 20px 20px 20px;">
    //                                                             <!-- Card Section -->
    //                                                             <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #FFFFFF; border-radius: 24px; margin-bottom: 0;">
    //                                                                 <tr>
    //                                                                     <td style="padding: 16px;">
    //                                                                         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    //                                                                             <tr>
    //                                                                                 <td width="180" style="vertical-align: top; padding-left: 8px; width: 30%;">
    //                                                                                     <img src="' . $image . '" alt="' . $title . '" width="180" style="display: block; border-radius: 8px; max-width: 180px; height: auto; width: 100%;">
    //                                                                                 </td>
    //                                                                                 <td style="vertical-align: top; padding: 0 16px; width: 70%;">
    //                                                                                     <h2 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700; color: #0D0D0D;">' . $title . '</h2>
    //                                                                                     <p style="margin: 0 0 8px 0; font-size: 16px; color: #FF5B4B; font-weight: 500;">' . ($activity_data->activityType->name_en ?? 'Activity') . '</p>
    //                                                                                     <p style="margin: 0 0 8px 0; font-size: 16px; color: #0D0D0D70;">' . $city . ', ' . $country . '</p>
    //                                                                                     ' /* .$ratingHtml */ . '
    //                                                                                     <table role="presentation" cellspacing="0" cellpadding="0" border="0">
    //                                                                                         <tr>
    //                                                                                             <td style="padding: 0;">
    //                                                                                                 <span style="color: #0D0D0D70; font-size: 18px;">📅</span>
    //                                                                                             </td>
    //                                                                                             <td style="padding: 2px 0 0 4px;">
    //                                                                                                 <span style="font-size: 16px; color: #0D0D0D; font-weight: 500;">' . $bookingDate . '</span>
    //                                                                                             </td>
    //                                                                                         </tr>
    //                                                                                     </table>
    //                                                                                 </td>
    //                                                                             </tr>
    //                                                                         </table>
    //                                                                     </td>
    //                                                                 </tr>

    //                                                                 <!-- Booking Details -->
    //                                                                 <tr>
    //                                                                     <td style="padding: 16px; border-top: 1px dashed #0D0D0D15;">
    //                                                                         <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #0D0D0D;">Booking details</h2>
    //                                                                         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #00909004; border: 1px dashed #00909015; border-radius: 16px;">
    //                                                                             <tr>
    //                                                                                 <td style="padding: 16px;">
    //                                                                                     <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-bottom: 1px dashed #0D0D0D10;">
    //                                                                                         <tr>
    //                                                                                             <td style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">Date</td>
    //                                                                                             <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">' . $bookingDate . '</td>
    //                                                                                         </tr>
    //                                                                                     </table>
    //                                                                                     <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-bottom: 1px dashed #0D0D0D10;">
    //                                                                                         <tr>
    //                                                                                             <td style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">Start time</td>
    //                                                                                             <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">' . $startTime . '</td>
    //                                                                                         </tr>
    //                                                                                     </table>
    //                                                                                     <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-bottom: 1px dashed #0D0D0D10;">
    //                                                                                         <tr>
    //                                                                                             <td style="padding: 16px 0; font-size: 16px; color: #0D0D0D70;">Guests</td>
    //                                                                                             <td align="right" style="padding: 16px 0; font-size: 18px; font-weight: 500; color: #0D0D0D;">' . $guests . '</td>
    //                                                                                         </tr>
    //                                                                                     </table>
    //                                                                                     ' . $optionalToolsHtml . '
    //                                                                                     ' . $additionalServicesHtml . '

    //                                                                                 </td>
    //                                                                             </tr>
    //                                                                         </table>
    //                                                                         <p style="margin: 8px 0 0 0; font-size: 14px; color: #0D0D0D70;">' . $bookedAt . '</p>
    //                                                                     </td>
    //                                                                 </tr>

    //                                                                 <!-- Booking Cost -->
    //                                                                 <tr>
    //                                                                     <td style="padding: 16px;">
    //                                                                         <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #0D0D0D;">Booking cost</h2>
    //                                                                         <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #00909004; border: 1px dashed #00909015; border-radius: 16px;">
    //                                                                             <tr>
    //                                                                                 <td style="padding: 16px;">
    //                                                                                     ' . $costBreakdownHtml . '
    //                                                                                     <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    //                                                                                         <tr>
    //                                                                                             <td align="left" style="padding: 16px 0; font-size: 20px; color: #0d0d0db4; font-weight: 500;">Total</td>
    //                                                                                             <td align="right" style="padding: 16px 0; font-size: 24px; font-weight: 700; color: #FF5B4B;">ريال ' . $total . '</td>
    //                                                                                         </tr>
    //                                                                                     </table>
    //                                                                                 </td>
    //                                                                             </tr>
    //                                                                         </table>
    //                                                                     </td>
    //                                                                 </tr>
    //                                                             </table>
    //                                                         </td>
    //                                                     </tr>
    //                                                 </table>

    //                                                 <!-- ZATCA -->
    //                                                 <a href="https://zatca.gov.sa/ar/E-Invoicing/Pages/default.aspx" style="display: block; text-align: center;">
    //                                                     <img src="https://wlmma.com/public/EmailTemplate-Wlmma/Image/Zakat-Tax-and-Customs-Authority-2-01.png" alt="ZATCA" width="300" style="display: block; margin: 20px auto; max-width: 150px; height: 50px; object-fit: cover; transform: scale(1.8);">
    //                                                 </a>
    //                                             </td>
    //                                         </tr>
    //                                     </table>
    //                                 </body>
    //                                 </html>';

    //                     $mail->Body = $html;

    //                     // $mail->send();

    //                     Log::info('SENT BOOKING payment email');
    //                 } catch (\Exception $e) {
    //                     Log::error('Failed to send payment completed email: ' . $e->getMessage());
    //                 }
    //             }
    //         } elseif (!empty($billing_information->cart_id)) {
    //             /* FOR ORDER */
    //             $cart = Cart::with('cartItems')->find($billing_information->cart_id);
    //             $order_data = Order::with('orderItems.tool')->where('user_id', auth()->id())->orderByDesc('id')->first() ?? null;
    //             // $order = Order::create([
    //             //     'user_id' => auth()->id(),
    //             //     'total' => 0,  // will update this later after adding items
    //             //     // 'lat' => $request->lat,
    //             //     // 'long' => $request->long,
    //             //     'location' => $request->location,
    //             //     'additional_details' => $request->additional_details,
    //             // ]);

    //             // Use tools from request if provided, otherwise fetch from cart
    //             // if ($request->tools && count($request->tools) > 0) {
    //             //     // Tools provided in request
    //             //     foreach ($request->tools as $item) {
    //             //         $tool = CommercialTool::find($item['tool_id']);
    //             //         if (!$tool)
    //             //             continue;

    //             //         $orderItem = OrderItem::where('order_id', $order->id)
    //             //             ->where('tool_id', $item['tool_id'])
    //             //             ->first();

    //             //         if ($orderItem) {
    //             //             $orderItem->quantity += $item['quantity'];
    //             //             $orderItem->save();
    //             //         } else {
    //             //             OrderItem::create([
    //             //                 'order_id' => $order->id,
    //             //                 'price' => $tool->price,
    //             //                 'tool_id' => $item['tool_id'],
    //             //                 'quantity' => $item['quantity'],
    //             //             ]);
    //             //         }
    //             //     }
    //             // } else {
    //             //     // Fetch tools from cart items
    //             //     foreach ($cart->cartItems ?? [] as $cartItem) {
    //             //         $tool = CommercialTool::find($cartItem->tool_id);
    //             //         if (!$tool)
    //             //             continue;

    //             //         OrderItem::create([
    //             //             'order_id' => $order->id,
    //             //             'price' => $tool->price,
    //             //             'tool_id' => $cartItem->tool_id,
    //             //             'quantity' => $cartItem->quantity,
    //             //         ]);
    //             //     }
    //             // }

    //             // Recalculate total price from all order items
    //             $total_price = 0;
    //             $all_order_items = OrderItem::where('order_id', $order_data->id)->get();

    //             foreach ($all_order_items as $item) {
    //                 $tool = CommercialTool::find($item->tool_id);
    //                 $total_price += $tool->price * $item->quantity;
    //             }

    //             // Update the order with the correct total
    //             $order_data->update([
    //                 'lat' => $request->lat,
    //                 'long' => $request->long,
    //                 'location' => $request->location,
    //                 'additional_details' => $request->additional_details,
    //                 'old_price' => $cart->old_price ?? 0,
    //                 'total' => $total_price,
    //             ]);

    //             $this->cartService->clearCart($billing_information->cart_id);

    //             /* FOR ORDER */
    //             $cart_data = Cart::with('cartItems')->find($billing_information->cart_id);

    //             // ✅ Fixed: filter by order_id (not id), use $order_data->id (not ->order_id)
    //             $tool_ids = OrderItem::where('order_id', $order_data->id)->get()->pluck('tool_id')->toArray();
    //             $tools = CommercialTool::whereIn('id', $tool_ids)->get();
    //             $provider_ids = $tools->pluck('user_id')->unique()->toArray();

    //             Log::info('Tools IDs => ', $tool_ids);
    //             Log::info('Provider IDs => ', $provider_ids);
    //             Log::info('Order DATA => ' . $order_data);
    //             //  dd($order_data);
    //             // $order_ids = $order_data->orderItems->pluck('tool_id')->toArray();

    //             if ($request->partial_wallet == 1 && $amount) {
    //                 // $allcartbook_total_price = $amount;  // Deduct from gateway

    //                 // Total Price of Cart
    //                 $allcart_total_price = Cart::where('user_id', auth()->id())
    //                     ->where('id', $billing_information->cart_id)
    //                     ->first()
    //                     ->total_price;

    //                 // Deduct from wallet
    //                 $deucted_amount_from_wallet = $allcart_total_price - $allcartbook_total_price;

    //                 $walletService->debit($user->id, null, $billing_information->cart_id, $deucted_amount_from_wallet, null, 'Cart fully paid from wallet');
    //             }

    //             // ==========================================
    //             // Mark Order as Paid & Generate ZATCA Invoice
    //             // ==========================================
    //             if (($code == '000.100.110' || $code == '000.100.101' || $code == '000.000.000') && $order_data) {
    //                 // Mark order as paid for refund tracking
    //                 $order_data->update([
    //                     'is_paid' => true,
    //                     'status' => 'completed',
    //                 ]);

    //                 $this->generateZatcaInvoiceForOrder($order_data);
    //             }

    //             $toolNamesEn = $tools->pluck('name_en')->join(', ');
    //             $toolNamesAr = $tools->pluck('name_ar')->join(', ');

    //             $notificationData = [
    //                 'title_en' => 'Ready for your next adventure? ✈️🌍',
    //                 // 'title_ar' => 'تم الدفع بنجاح',
    //                 'title_ar' => 'جاهز لمغامرتك الجاية؟ ✈️🌍',
    //                 // 'body_en' => "A tool $toolNamesEn has been bought.",
    //                 'body_en' => 'Ready for your next adventure? ✈️🌍',
    //                 // 'body_ar' => "تم شراء الاداة $toolNamesAr",
    //                 'body_ar' => 'جاهز لمغامرتك الجاية؟ ✈️🌍',
    //                 'type' => 'payment_success',
    //                 'book_id' => null,
    //                 'order_id' => $order_data->id ?? null,
    //                 'action' => 'order',
    //                 'send_to_all' => true,
    //             ];

    //             $pushController = new NotificationController;
    //             $pushController->pushWithoutBookId2(new PushNotificationRequest($notificationData), $provider_ids);

    //             // Send email to all providers
    //             $provider_emails = User::whereIn('id', $provider_ids)->pluck('email', 'id');
    //             foreach ($provider_emails as $provider_id => $user_email) {
    //                 if ($user_email) {
    //                     $user_name = User::where('id', $provider_id)->value('name');
    //                     try {
    //                         // Mail::to($user_email)->send(new BookingNotificationMail(
    //                         //     customerName: $user_name ?? 'Valued Customer',
    //                         //     activityTitle: $toolNamesEn,
    //                         //     bookingStatus: 'paid',
    //                         //     notificationType: 'payment_completed'
    //                         // ));

    //                         $tomemail = $user->email;
    //                         sendViewEmail($tomemail, __('Order Details'), 'emails.booking-notification', [
    //                             'customerName' => $user_name ?? 'Valued Customer',
    //                             'activityTitle' => $toolNamesEn,
    //                             'bookingStatus' => 'paid',
    //                             'notificationType' => 'payment_completed',
    //                         ]);

    //                         Log::info('Email => ' . $tomemail);
    //                         $mail = new PHPMailer(true);
    //                         $mail->isSMTP();
    //                         $mail->Host = 'notifications.wlmma.com';
    //                         $mail->SMTPAuth = true;
    //                         $mail->Username = env('MAIL_USERNAME');
    //                         $mail->Password = env('MAIL_PASSWORD');
    //                         // $mail->Password   = "I,Lrs63ACr~[";
    //                         $mail->SMTPSecure = 'ssl';
    //                         $mail->Port = 465;

    //                         $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
    //                         $mail->addAddress($tomemail);

    //                         $mail->isHTML(true);
    //                         $mail->Subject = 'Order Details';

    //                         $templateUrl = 'https://wlmma.com/public/EmailTemplate-Wlmma/tool.php';
    //                         $html = file_get_contents($templateUrl);
    //                         if (!$order_data) {
    //                             Log::warning('No order found for user_id: ' . auth()->id());

    //                             return response()->json(['message' => 'No order found'], 404);
    //                         }

    //                         // Build PRODUCT_ROWS HTML dynamically
    //                         $orderItems = $order_data->orderItems ?? collect();
    //                         $productRowsHtml = '';
    //                         $sumSubTotal = 0;
    //                         foreach ($orderItems as $item) {
    //                             $toolName = $item->tool->name_en ?? 'Tool';
    //                             $quantity = $item->quantity ?? 0;
    //                             $subTotal = number_format($item->tool->price ?? 0, 2);
    //                             $sumSubTotal += $subTotal * $quantity;

    //                             $productRowsHtml .= "
    //                                 <tr>
    //                                     <td align='left' width='40%' style='padding:16px 0; font-size:16px; color:#0D0D0D;'>{$toolName}</td>
    //                                     <td align='center' style='padding:0; font-size:16px; font-weight:400; color:#0D0D0D;'>X {$quantity}</td>
    //                                     <td align='right' style='padding:16px 0; font-size:18px; font-weight:500; color:#0D0D0D;'>ريال {$subTotal}</td>
    //                                 </tr>
    //                             ";
    //                         }

    //                         $totalSubtotal = $sumSubTotal;
    //                         $total = $totalSubtotal;

    //                         Log::info('ORDER ID => ' . $order_data->id);
    //                         $location = $order_data->location ?? '-';
    //                         Log::info('Location => ' . $location);
    //                         $additionalDetails = $order_data->additional_details ?? '-';
    //                         Log::info('additional_details => ' . $additionalDetails);
    //                         $bookingDate = $order_data->created_at ? $order_data->created_at->format('Y, F d · h:i A') : '-';
    //                         /* FOR ORDER */
    //                         $this->cartService->clearCart($request->cart_id);
    //                         /* FOR ORDER */

    //                         // Build full HTML email
    //                         // $html = "
    //                         //         <!DOCTYPE html>
    //                         //         <html lang='en'>
    //                         //         <head>
    //                         //             <meta charset='UTF-8'>
    //                         //             <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    //                         //             <title>Booking Details</title>
    //                         //         </head>
    //                         //         <body style='margin:0; padding:0; font-family: Arial, sans-serif; background-color:#FFFCFC;'>
    //                         //             <table width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color:#FFFCFC;'>
    //                         //                 <tr>
    //                         //                     <td align='center'>
    //                         //                         <table width='650' style='max-width:650px; background-color:#FFFCFC;'>
    //                         //                             <tr>
    //                         //                                 <td align='center' style='padding:60px 20px 40px 20px;'>
    //                         //                                     <img src='https://wlmma.com/public/EmailTemplate-Wlmma/Image/logo.png' width='150' style='display:block; margin:0 auto 16px auto;'>
    //                         //                                 </td>
    //                         //                             </tr>
    //                         //                         </table>

    //                         //                         <table width='650' style='max-width:650px;'>
    //                         //                             <tr>
    //                         //                                 <td style='padding:0 20px 30px 20px;'>
    //                         //                                     <table width='100%' style='background-color:#FFFFFF; border-radius:24px;'>
    //                         //                                         <tr>
    //                         //                                             <td style='padding:16px;'>
    //                         //                                                 <h2 style='margin:0 0 16px 0; font-size:20px; font-weight:700;'>Products</h2>
    //                         //                                                 <table width='100%' cellspacing='0' cellpadding='0' border='0'>
    //                         //                                                     {$productRowsHtml}
    //                         //                                                 </table>
    //                         //                                                 <p style='margin:8px 0 0 0; font-size:14px; color:#0D0D0D70;'>{$bookingDate}</p>
    //                         //                                             </td>
    //                         //                                         </tr>

    //                         //                                         <tr>
    //                         //                                             <td style='padding:16px;'>
    //                         //                                                 <h2 style='margin:0 0 16px 0; font-size:20px; font-weight:700;'>Order Details</h2>
    //                         //                                                 <table width='100%' style='background-color:#00909004; border:1px dashed #00909015; border-radius:16px;'>
    //                         //                                                     <tr>
    //                         //                                                         <td style='padding:16px;'>
    //                         //                                                             <table width='100%' style='border-bottom:1px dashed #0D0D0D10;'>
    //                         //                                                                 <tr>
    //                         //                                                                     <td style='padding:16px 0 0 0;'>
    //                         //                                                                         <p style='margin:0 0 12px 0; font-size:16px; color:#0D0D0D70;'>Delivery location</p>
    //                         //                                                                         <p style='margin:0 0 12px 0; font-size:18px; font-weight:500; color:#0D0D0D;'>{$location}</p>
    //                         //                                                                     </td>
    //                         //                                                                 </tr>
    //                         //                                                             </table>

    //                         //                                                             <table width='100%' style='border-bottom:1px dashed #0D0D0D10;'>
    //                         //                                                                 <tr>
    //                         //                                                                     <td style='padding:16px 0 0 0;'>
    //                         //                                                                         <p style='margin:0 0 12px 0; font-size:16px; color:#0D0D0D70;'>Additional Details</p>
    //                         //                                                                         <p style='margin:0 0 12px 0; font-size:18px; font-weight:500; color:#0D0D0D;'>{$additionalDetails}</p>
    //                         //                                                                     </td>
    //                         //                                                                 </tr>
    //                         //                                                             </table>

    //                         //                                                             <table width='100%' style='border-bottom:1px dashed #0D0D0D10;'>
    //                         //                                                                 <tr>
    //                         //                                                                     <td align='left' style='padding:16px 0; font-size:18px; font-weight:500; color:#0D0D0D;'>Subtotal</td>
    //                         //                                                                     <td align='right' style='padding:16px 0; font-size:18px; font-weight:500; color:#0D0D0D;'>ريال " . number_format($totalSubtotal, 2) . "</td>
    //                         //                                                                 </tr>
    //                         //                                                             </table>

    //                         //                                                             <table width='100%'>
    //                         //                                                                 <tr>
    //                         //                                                                     <td align='left' style='padding:16px 0; font-size:20px; font-weight:500; color:#0D0D0DB4;'>Total with 12% tax </td>
    //                         //                                                                     <td align='right' style='padding:16px 0; font-size:24px; font-weight:700; color:#FF5B4B;'>ريال " . number_format($total, 2) . "</td>
    //                         //                                                                 </tr>
    //                         //                                                             </table>
    //                         //                                                         </td>
    //                         //                                                     </tr>
    //                         //                                                 </table>
    //                         //                                             </td>
    //                         //                                         </tr>
    //                         //                                     </table>
    //                         //                                 </td>
    //                         //                             </tr>
    //                         //                         </table>

    //                         //                         <a href='https://zatca.gov.sa/ar/E-Invoicing/Pages/default.aspx'>
    //                         //                             <img src='https://wlmma.com/public/EmailTemplate-Wlmma/Image/Zakat-Tax-and-Customs-Authority-2-01.png' width='300' style='display:block; margin:0 auto;'>
    //                         //                         </a>
    //                         //                     </td>
    //                         //                 </tr>
    //                         //             </table>
    //                         //         </body>
    //                         //         </html>
    //                         //         ";

    //                         // $mail->Body = $html;

    //                         // $mail->isHTML(true);
    //                         // $mail->CharSet = 'UTF-8';

    //                         // $mail->Body = str_replace(
    //                         //     array_keys($replacements),
    //                         //     array_values($replacements),
    //                         //     $html
    //                         // );

    //                         // $mail->Body = str_replace(array_keys($replacements), array_values($replacements), $html);

    //                         // $mail->send();

    //                         Log::info('SENT order payment email');
    //                     } catch (\Exception $e) {
    //                         Log::error('Failed to send order payment email: ' . $e->getMessage());
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     // Add ZATCA QR code to response if available
    //     $zatcaData = $this->getZatcaDataForResponse($billing_information);
    //     if ($zatcaData) {
    //         $response['zatca'] = $zatcaData;
    //     }

    //     return response()->json($response);
    // }

    // ================================================================
    // FIXED: getPaymentStatus - ORDER FLOW ONLY
    // ================================================================

    public function getPaymentStatus(Request $request)
    {
        $request->validate([
            'partial_wallet' => 'nullable',
            'checkoutId' => 'required|string',
            'payment_method' => 'required|in:visa,master,mada,applepay',
            'additional_details' => 'nullable|string',
            'location' => 'nullable|string',
            'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
            'tools.*.quantity' => 'required|integer|min:1',
        ]);

        // ── 1. Get HyperPay status ────────────────────────────────
        $response = $this->hyperPayService->getPaymentStatus(
            $request->checkoutId,
            $request->payment_method
        );

        // ── 2. Find billing info ──────────────────────────────────
        $billing_information = BillingInformation::where('payment_checkout_id', $request->checkoutId)->first();

        if (!$billing_information) {
            Log::error('BillingInformation not found for checkoutId: ' . $request->checkoutId);
            return response()->json(['message' => 'Billing information not found'], 404);
        }

        $code = $response['result']['code'] ?? null;
        $description = $response['result']['description'] ?? null;

        $isPaymentSuccessful = $code && str_starts_with($code, '000.');

        // ── 3. Update billing info ────────────────────────────────
        $billing_information->update([
            'payment_status' => $code,
            'describtion' => $description,
            'registration_id' => $response['registrationId'] ?? null,
            'payment_id' => $response['id'] ?? null,
        ]);

        Log::info('HyperPay Payment Result', [
            'checkout_id' => $request->checkoutId,
            'payment_id' => $response['id'] ?? null,
            'code' => $code,
            'description' => $description,
            'is_successful' => $isPaymentSuccessful,
            'cart_id' => $billing_information->cart_id,
            'book_id' => $billing_information->book_id,
        ]);

        // ── 4. Only proceed if payment succeeded ──────────────────
        if ($isPaymentSuccessful) {
            $user = Auth::user();
            $walletService = new WalletService;

            // FIX: define $amount once, used in both booking and cart flows
            $amount = $billing_information->amount;

            // Save registrationId on user if present
            if (!empty($response['registrationId'])) {
                $user->update(['registration_id' => $response['registrationId']]);
            }

            // ══════════════════════════════════════════════════════
            // BOOKING FLOW
            // ══════════════════════════════════════════════════════
            if (!empty($billing_information->book_id)) {
                $booking_data = Booking::find($billing_information->book_id);
                if (!$booking_data) {
                    Log::warning('No BOOKING found for book_id: ' . $billing_information->book_id);
                    return response()->json(['message' => 'No BOOKING found'], 404);
                }

                $activity_data = Activity::with('activityImages', 'activityType', 'serviceprovider')
                    ->join('bookings', 'activities.id', '=', 'bookings.activity_id')
                    ->where('bookings.id', $billing_information->book_id)
                    ->select('activities.*', 'bookings.capacity')
                    ->first();

                // Partial wallet deduction
                if ($request->partial_wallet == 1 && $amount) {
                    $allbook_total_price = Booking::where('user_id', auth()->id())
                        ->where('id', $billing_information->book_id)
                        ->value('total_price');

                    $deducted_from_wallet = $allbook_total_price - $amount;
                    $walletService->debit(
                        $user->id,
                        $billing_information->book_id,
                        null,
                        $deducted_from_wallet,
                        null,
                        'Booking fully paid from wallet'
                    );
                }

                // Mark booking as paid
                if (in_array($code, ['000.100.110', '000.100.101', '000.000.000'])) {
                    $booking_data->update([
                        'status_id' => 5,
                        'is_paied' => 1,
                    ]);

                    $this->generateZatcaInvoiceForBooking($booking_data);
                }

                // Notification to provider
                $notificationData = [
                    'title_en' => 'Booking Payment Completed',
                    'title_ar' => 'تم الدفع بنجاح',
                    'body_en' => "A trip {$activity_data->title_en} has been paid.",
                    'body_ar' => "تم دفع الرحلة '{$activity_data->title_ar}'",
                    'type' => 'payment_success',
                    'book_id' => $booking_data->id,
                    'action' => 'payment',
                    'send_to_all' => false,
                ];

                $pushController = new NotificationController;
                $pushController->push_welcome(
                    new PushNotificationRequest($notificationData),
                    $activity_data->user_id
                );

                // FIX: send email to provider, not customer
                $provider_email = User::where('id', $activity_data->user_id)->value('email');
                if ($provider_email) {
                    try {
                        $this->sendBookingEmail($provider_email, $booking_data, $activity_data);
                        Log::info('SENT BOOKING payment email to provider: ' . $provider_email);
                    } catch (\Exception $e) {
                        Log::error('Failed to send booking payment email: ' . $e->getMessage());
                    }
                }

                // ══════════════════════════════════════════════════════
                // ORDER FLOW (Cart)
                // ══════════════════════════════════════════════════════
            } elseif (!empty($billing_information->cart_id)) {

    // ✅ جيب الـ Order الموجود بالفعل (مش تعمل واحد جديد)
    $order = Order::find($billing_information->order_id);

    if (!$order) {
        Log::error('Order not found for billing_id: ' . $billing_information->id);
        return response()->json(['message' => 'Order not found'], 404);
    }

    // ✅ لو الـ payment ناجح فقط اعمل update
    if (in_array($code, ['000.100.110', '000.100.101', '000.000.000'])) {

        // ✅ تحقق إنه مش اتدفع قبل كده (idempotency)
        if ($order->is_paid) {
            Log::info('Order already paid, skipping: ' . $order->id);
            return response()->json($response);
        }

        $order->update([
            'location'           => $request->location,
            'additional_details' => $request->additional_details,
            'is_paid'            => true,
            'status'             => 'completed',
        ]);
         $this->generateZatcaInvoiceForOrder($order);
                    $this->cartService->clearCart($billing_information->cart_id); // 
    }

        
        

                // ── Recalculate total ─────────────────────────────────
                $total_price = 0;
                $all_order_items = OrderItem::where('order_id', $order->id)->get();

                foreach ($all_order_items as $item) {
                    $total_price += $item->price * $item->quantity;
                }

                $order->update(['total' => $total_price]);

                // ── Partial wallet deduction ──────────────────────────
                if ($request->partial_wallet == 1 && $amount) {
                    $allcart_total_price = Cart::where('user_id', auth()->id())
                        ->where('id', $billing_information->cart_id)
                        ->value('total_price');

                    $deducted_from_wallet = $allcart_total_price - $amount;
                    $walletService->debit(
                        $user->id,
                        null,
                        $billing_information->cart_id,
                        $deducted_from_wallet,
                        null,
                        'Cart fully paid from wallet'
                    );
                }

                // ── Mark order as paid & ZATCA ────────────────────────
                // if (in_array($code, ['000.100.110', '000.100.101', '000.000.000'])) {
                //     $order->update([
                //         'is_paid' => true,
                //         'status' => 'completed',
                //     ]);

                //     $this->generateZatcaInvoiceForOrder($order);
                //     $this->cartService->clearCart($billing_information->cart_id); // مرة واحدة بس
                // }

                // FIX: clear cart only ONCE using billing_information->cart_id
                // $this->cartService->clearCart($billing_information->cart_id);

                // ── Notify providers ──────────────────────────────────
                $tool_ids = $all_order_items->pluck('tool_id')->toArray();
                $tools = CommercialTool::whereIn('id', $tool_ids)->get();
                $provider_ids = $tools->pluck('user_id')->unique()->toArray();
                $toolNamesEn = $tools->pluck('name_en')->join(', ');
                $toolNamesAr = $tools->pluck('name_ar')->join(', ');

                Log::info('Order created', [
                    'order_id' => $order->id,
                    'tool_ids' => $tool_ids,
                    'provider_ids' => $provider_ids,
                    'total' => $total_price,
                ]);

                $notificationData = [
                    'title_en' => 'Order Payment Completed',
                    'title_ar' => 'تم الدفع بنجاح',
                    'body_en' => "A tool $toolNamesEn has been bought .",
                    'body_ar' => "تم دفع الاداة '{$toolNamesAr}' ",
                    'type' => 'payment_success',
                    'book_id' => null,
                    'order_id' => $order->id,
                    'action' => 'order',
                    'send_to_all' => true,
                ];
                
                
                // $notificationData = [
                //     'title_en' => 'Ready for your next adventure? ✈️🌍',
                //     // 'title_ar' => 'تم الدفع بنجاح',
                //     'title_ar' => 'جاهز لمغامرتك الجاية؟ ✈️🌍',
                //     // 'body_en' => "A tool $toolNamesEn has been bought.",
                //     'body_en' => 'Ready for your next adventure? ✈️🌍',
                //     // 'body_ar' => "تم شراء الاداة $toolNamesAr",
                //     'body_ar' => 'جاهز لمغامرتك الجاية؟ ✈️🌍',
                //     'type' => 'payment_success',
                //     'book_id' => null,
                //     'order_id' => $order_data->id ?? null,
                //     'action' => 'order',
                //     'send_to_all' => true,
                // ];
                
                
             

                $pushController = new NotificationController;
                $pushController->pushWithoutBookId2(
                    new PushNotificationRequest($notificationData),
                    $provider_ids
                );

                // FIX: send email to each PROVIDER (not $user->email)
                $provider_emails = User::whereIn('id', $provider_ids)->pluck('email', 'id');
                foreach ($provider_emails as $provider_id => $provider_email) {
                    if (!$provider_email)
                        continue;
                    try {
                        $this->sendOrderEmail($provider_email, $order, $all_order_items);
                        Log::info('SENT order payment email to provider: ' . $provider_email);
                    } catch (\Exception $e) {
                        Log::error('Failed to send order payment email to ' . $provider_email . ': ' . $e->getMessage());
                    }
                }
            }
        }

        // ── ZATCA QR in response ──────────────────────────────────────
        $zatcaData = $this->getZatcaDataForResponse($billing_information);
        if ($zatcaData) {
            $response['zatca'] = $zatcaData;
        }

        return response()->json($response);
    }

    /**
     * Generate ZATCA e-invoice for a booking.
     */
    protected function generateZatcaInvoiceForBooking(Booking $booking): void
    {
        try {
            // Load required relationships
            $booking->load('activity');

            // Generate and submit invoice to ZATCA with email notification
            $result = $this
                ->invoiceService
                ->withEmailNotifications()
                ->createFromBooking($booking);

            if ($result && $result->isSuccess()) {
                Log::info('ZATCA invoice generated successfully for booking', [
                    'booking_id' => $booking->id,
                    'invoice_number' => $booking->getInvoiceNumber(),
                    'status' => $result->getProcessType(),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the payment
            Log::error('ZATCA invoice generation failed for booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate ZATCA e-invoice for an order.
     */
    protected function generateZatcaInvoiceForOrder(Order $order): void
    {
        try {
            // Load required relationships
            $order->load('orderItems.tool', 'user');

            // Generate and submit invoice to ZATCA with email notification
            $result = $this
                ->invoiceService
                ->withEmailNotifications()
                ->createFromOrder($order);

            if ($result && $result->isSuccess()) {
                Log::info('ZATCA invoice generated successfully for order', [
                    'order_id' => $order->id,
                    'invoice_number' => $order->getInvoiceNumber(),
                    'status' => $result->getProcessType(),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the payment
            Log::error('ZATCA invoice generation failed for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get ZATCA data for the payment response.
     */
    protected function getZatcaDataForResponse(?BillingInformation $billing): ?array
    {
        if (!$billing) {
            return null;
        }

        $entity = null;
        if ($billing->book_id) {
            $entity = Booking::find($billing->book_id);
        } elseif ($billing->cart_id) {
            $entity = Order::where('user_id', auth()->id())->latest()->first();
        }

        if (!$entity || !$entity->zatca_qr_code) {
            return null;
        }

        return [
            'invoice_number' => $entity->invoice_number,
            'qr_code' => $entity->zatca_qr_code,
            'status' => $entity->zatca_status,
        ];
    }
}
