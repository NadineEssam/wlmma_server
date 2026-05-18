<?php

namespace App\Services;

use App\Models\BillingInformation;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HyperPayService
{
    private $authToken;
    private $baseUrl;

    public function __construct()
    {
        // Test Mode
        // $this->authToken = 'Bearer OGFjN2E0Y2E5NGQ2MmE4ZDAxOTRkYzYxZGI0NDEwMzd8amdOcVFlaEo5cVduOWFRc3kjWGk=';
        // $this->baseUrl = 'https://eu-test.oppwa.com/v1/';

        // Production Mode
        // $this->authToken = 'Bearer OGFjZGE0Y2Q5OWM4M2Y5ODAxOTljOGEyZDNkNjA3NWJ8UnVmI0tNSzZSSjkyWGZBQmdhNmQ=';
        // $this->baseUrl = 'https://eu-prod.oppwa.com/v1/';
        $this->authToken = 'Bearer ' . env('HYPERPAY_AUTH_TOKEN');
        $this->baseUrl = env('HYPERPAY_BASE_URL');
    }

    // Determine Entity ID dynamically based on card type
    private function getEntityId($paymentMethod)
    {
        /* Test Mode */
        // return match (strtolower($paymentMethod)) {
        //     'mada' => '8ac7a4ca94d62a8d0194dc62f63d103f',
        //     'applepay' => '8ac7a4ca94d62a8d0194dc62f63d103f',
        //     default => '8ac7a4ca94d62a8d0194dc625aa7103b',  // Visa & MasterCard
        // };

        /* Production Mode */
        return match (strtolower($paymentMethod)) {
            // 'mada' => '8acda4cd99c83f980199c8a344c90766',
            // 'applepay' => '8ac7a4da9b6c2358019b6ef75088052f',
            // default => '8acda4cd99c83f980199c8a344c90766',  // Visa & MasterCard
            'mada' => env('HYPERPAY_ENTITY_ID_MADA'),
            'applepay' => env('HYPERPAY_ENTITY_APPLE_PAY'),
            default => env('HYPERPAY_ENTITY_ID_VISA'),  // Visa & MasterCard
        };
    }

    // Step 1: Create Checkout & Save Card

    // public function createCheckout(
    //     $userId,
    //     $email,
    //     $street,
    //     $city,
    //     $state,
    //     $country,
    //     $postcode,
    //     $firstName,
    //     $lastName,
    //     $amount,
    //     $cart_id,
    //     $book_id,
    //     $paymentMethod
    // ) {
    //     $entityId = $this->getEntityId($paymentMethod);

    //     $data = [
    //         'entityId' => $entityId,
    //         'amount' => $amount,
    //         'currency' => 'SAR',
    //         'paymentType' => 'DB',
    //         'createRegistration' => 'true',  // Save card for future use
    //         'merchantTransactionId' => 'USER_' . $userId . '_' . uniqid(),  // Include user ID
    //         'customer.email' => $email,
    //         'billing.street1' => $street,
    //         'billing.city' => $city,
    //         'billing.state' => $state,
    //         'billing.country' => $country,
    //         'billing.postcode' => $postcode,
    //         'customer.givenName' => $firstName,
    //         'customer.surname' => $lastName,
    //     ];

    //     $response = Http::withHeaders([
    //         'Authorization' => $this->authToken,
    //     ])->asForm()->post($this->baseUrl . 'checkouts', $data);
    //     // dd($response);

    //     $bill_data = BillingInformation::create([
    //         'user_id' => $userId,
    //         'payment_checkout_id' => $response['id'],
    //         'first_name' => $firstName,
    //         'last_name' => $lastName,
    //         'email' => $email,
    //         'street' => $street,
    //         'city' => $city,
    //         'state' => $state,
    //         'country' => $country,
    //         'postcode' => $postcode,
    //         'amount' => $amount,
    //         'cart_id' => $cart_id,
    //         'book_id' => $book_id,
    //         'payment_method' => $paymentMethod,
    //     ]);
    //     Order::create([
    //         'user_id' => $userId,
    //         'total' => $amount,
    //         'status' => 'pending',
    //     ]);

    //     // dd($bill_data);

    //     return $response->json();
    // }

    public function createCheckout(
        $userId,
        $email,
        $street,
        $city,
        $state,
        $country,
        $postcode,
        $firstName,
        $lastName,
        $amount,
        $cart_id,
        $book_id,
        $paymentMethod,
        $order_id
    ) {
        $entityId = $this->getEntityId($paymentMethod);

        $data = [
            'entityId' => $entityId,
            'amount' => $amount,
            'currency' => 'SAR',
            'paymentType' => 'DB',
            'merchantTransactionId' => 'USER_' . $userId . '_' . uniqid(),
            'customer.email' => $email,
            'billing.street1' => $street,
            'billing.city' => $city,
            'billing.state' => $state,
            'billing.country' => $country,
            'billing.postcode' => $postcode,
            'customer.givenName' => $firstName,
            'customer.surname' => $lastName,
        ];

        // لو Apple Pay، أضف الـ payment brand
        // if (strtolower($paymentMethod) === 'apple' || strtolower($paymentMethod) === 'applepay') {
        //     $data['paymentBrand'] = 'APPLEPAY';
        //     // Apple Pay مش بيحتاج createRegistration
        //     unset($data['createRegistration']);
        // } else {
        //     $data['createRegistration'] = 'true';
        // }

        // // ✓ FIX: Apple Pay يحتاج paymentBrand و لا يحتاج createRegistration
        // if (strtolower($paymentMethod) === 'apple' || strtolower($paymentMethod) === 'applepay') {
        //     $data['paymentBrand'] = 'APPLEPAY';
        //     // ✓ لا تضيف createRegistration على الإطلاق للـ Apple Pay
        // } else {
        //     $data['createRegistration'] = 'true';
        // }

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(3, 1000)
                ->withHeaders([
                    'Authorization' => $this->authToken,
                ])
                ->asForm()
                ->post($this->baseUrl . 'checkouts', $data);

            if ($response->failed()) {
                Log::error('HyperPay Checkout Failed', [
                    'payment_method' => $paymentMethod,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to create checkout: ' . $response->body());
            }
            Log::info(json_decode($response->getBody(), true));

            $bill_data = BillingInformation::create([
                'user_id' => $userId,
                'order_id' => $order_id,   
                'payment_checkout_id' => $response['id'],  // تأكد أنه string
                'entity_id' => $entityId,  // ✓ احفظ entity_id هنا
                // 'payment_checkout_id' => $response['id'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postcode' => $postcode,
                'amount' => $amount,
                'cart_id' => $cart_id,
                'book_id' => $book_id,
                'payment_method' => $paymentMethod,
                'order_id' => $order_id,
            ]);

            Log::info('Payment Methode => ' . $paymentMethod);
            Log::info('entityId => ' . $entityId);
            Log::info('Checkout id => ' . $response['id']);

            // Order::create([
            //     'user_id' => $userId,
            //     'total' => $amount,
            //     'status' => 'pending',
            // ]);

            // return $response->json();

            $responseData = $response->json();
            $responseData['entity_id'] = $entityId;

            return $responseData;
        } catch (\Exception $e) {
            Log::error('HyperPay Checkout Exception', [
                'message' => $e->getMessage(),
                'payment_method' => $paymentMethod
            ]);
            throw $e;
        }
    }

    // Step 2: Get Payment Status
    public function getPaymentStatus($checkoutId, $paymentMethod)
    {
        // $entityId = $this->getEntityId($paymentMethod);
        $billing = BillingInformation::where('payment_checkout_id', $checkoutId)->first();
        $entityId = $billing?->entity_id ?? $this->getEntityId($paymentMethod);

        $response = Http::withHeaders([
            'Authorization' => $this->authToken,
        ])->get($this->baseUrl . 'checkouts/' . $checkoutId . '/payment', [
            'entityId' => $entityId,
        ]);

        return $response->json();
    }

    // public function payWithApplePay(string $amount, array $token): array
    // {
    //     return Http::asForm()
    //         ->withToken(env('HYPERPAY_AUTH_TOKEN'))
    //         ->post(env('HYPERPAY_BASE_URL') . 'v1/payments', [
    //             'entityId' => $this->getEntityId('applepay'),
    //             'amount' => $amount,
    //             'currency' => env('HYPERPAY_CURRENCY'),
    //             'paymentType' => 'DB',
    //             'applePay.token' => json_encode($token),
    //         ])
    //         ->json();
    // }

    // public function payWithApplePay(string $amount, array $token): array
    // {
    //     try {
    //         $response = Http::timeout(30)  // زود الـ timeout
    //             ->connectTimeout(10)  // وقت الاتصال
    //             ->retry(3, 1000)  // 3 محاولات
    //             ->asForm()
    //             ->withHeaders([
    //                 'Authorization' => $this->authToken,
    //             ])
    //             ->post($this->baseUrl . 'payments', [
    //                 'entityId' => $this->getEntityId('applepay'),
    //                 'amount' => $amount,
    //                 'currency' => 'SAR',
    //                 'paymentType' => 'DB',
    //                 'applePay.token' => json_encode($token),
    //             ]);

    //         if ($response->failed()) {
    //             Log::error('Apple Pay Payment Failed', [
    //                 'status' => $response->status(),
    //                 'body' => $response->body()
    //             ]);

    //             return [
    //                 'result' => [
    //                     'code' => 'ERROR.APPLEPAY.FAILED',
    //                     'description' => 'Apple Pay payment failed'
    //                 ]
    //             ];
    //         }

    //         Log::info('Apple Pay Payment Success', [
    //             'response' => $response->json()
    //         ]);

    //         return $response->json();
    //     } catch (\Illuminate\Http\Client\ConnectionException $e) {
    //         Log::error('Apple Pay Connection Exception', [
    //             'message' => $e->getMessage()
    //         ]);

    //         return [
    //             'result' => [
    //                 'code' => 'ERROR.APPLEPAY.TIMEOUT',
    //                 'description' => 'Connection timeout during Apple Pay transaction'
    //             ]
    //         ];
    //     } catch (\Exception $e) {
    //         Log::error('Apple Pay General Exception', [
    //             'message' => $e->getMessage()
    //         ]);

    //         return [
    //             'result' => [
    //                 'code' => 'ERROR.APPLEPAY.UNKNOWN',
    //                 'description' => 'An error occurred during Apple Pay transaction'
    //             ]
    //         ];
    //     }
    // }

    public function initiateApplePay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string|size:2',
            'postcode' => 'required|string',
            'cart_id' => 'nullable|exists:carts,id',
            'book_id' => 'nullable|exists:bookings,id',
        ]);

        // استخدم الـ createCheckout العادي مع Apple Pay
        $checkout = $this->hyperPayService->createCheckout(
            userId: auth()->id(),
            email: $request->email,
            street: $request->street,
            city: $request->city,
            state: $request->state,
            country: $request->country,
            postcode: $request->postcode,
            firstName: $request->first_name,
            lastName: $request->last_name,
            amount: $request->amount,
            cart_id: $request->cart_id,
            book_id: $request->book_id,
            paymentMethod: 'applepay'  // هنا بتحدد Apple Pay
        );

        return response()->json([
            'success' => true,
            'data' => $checkout
        ]);
    }
}
