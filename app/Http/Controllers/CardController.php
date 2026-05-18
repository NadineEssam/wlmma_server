<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\BillingInformation;
use Illuminate\Support\Facades\Auth;
use App\Services\Payment\HyperPayService;

class CardController extends Controller
{
    protected $hyperPayService;

    public function __construct(HyperPayService $hyperPayService)
    {
        $this->hyperPayService = $hyperPayService;
    }

    /**
     * Tokenize and save a card
     */
    // public function saveCard(Request $request)
    // {
    //     $request->validate([
    //         'number' => 'required|digits_between:13,19',
    //         'holder' => 'required|string',
    //         'expiry_month' => 'required|digits:2',
    //         'expiry_year' => 'required|digits:4',
    //         'cvv' => 'required|digits_between:3,4',
    //     ]);

    //     $response = $this->hyperPayService->tokenizeCard($request->all());

    //     return response()->json($response);
    // }
    // public function tokenizeCard(Request $request)
    // {
    //     $request->validate([
    //         'checkout_id' => 'required|exists:billing_information,payment_checkout_id',
    //         'number' => 'required|string',
    //         'holder' => 'required|string',
    //         'expiry_month' => 'required|string|size:2',
    //         'expiry_year' => 'required|string|size:4',
    //         'cvv' => 'required|string',
    //         'payment_method' => 'required|in:VISA,MASTER,MADA'
    //     ]);

    //     $cardData = $request->only(['number', 'holder', 'expiry_month', 'expiry_year', 'cvv']);
    //     $paymentMethod = $request->input('payment_method');

    //     $tokenResponse = $this->hyperPayService->tokenizeCard($cardData, $paymentMethod);
    //     // $fullName = $request->holder;
    //     // $firstName = Str::words($fullName, 1, '');
    //     // $lastName = trim(Str::after($fullName, $firstName));

    //     $add_card_number = BillingInformation::where('user_id', Auth::id())->where('payment_checkout_id', $request->checkout_id)->first();
    //     // dd($add_card_number);
    //     /*$done =*/
    //     $add_card_number->update([
    //         'card_number' => $request->number,
    //     ]);
    //     // dd($done);

    //     return response()->json($tokenResponse);
    // }

    // public function tokenizeCard(Request $request)
    // {
    //     // Validate the request data
    //     $validatedData = $request->validate([
    //         'checkout_id' => 'required|exists:billing_information,payment_checkout_id',
    //         'number' => 'required|string',
    //         'holder' => 'required|string',
    //         'expiry_month' => 'required|string|size:2',
    //         'expiry_year' => 'required|string|size:4',
    //         'cvv' => 'required|string',
    //         'payment_method' => 'required|in:VISA,MASTER,MADA'
    //     ]);

    //     // Extract card data and payment method from the request
    //     $cardData = $request->only(['number', 'holder', 'expiry_month', 'expiry_year', 'cvv']);
    //     $paymentMethod = $request->input('payment_method');

    //     // Tokenize the card using HyperPay service
    //     $tokenResponse = $this->hyperPayService->tokenizeCard($cardData, $paymentMethod);

    //     // Check if the tokenization was successful
    //     if (isset($tokenResponse['error'])) {
    //         return response()->json([
    //             'error' => 'Failed to tokenize card',
    //             'message' => $tokenResponse['message']
    //         ], 400);
    //     }

    //     // Update the billing information with the card number
    //     $billingInformation = BillingInformation::where('user_id', Auth::id())
    //         ->where('payment_checkout_id', $request->checkout_id)
    //         ->first();

    //     if (!$billingInformation) {
    //         return response()->json([
    //             'error' => 'Billing information not found'
    //         ], 404);
    //     }

    //     $billingInformation->update([
    //         'card_number' => $request->number,
    //     ]);

    //     // Return the tokenization response
    //     return response()->json($tokenResponse);
    // }


    public function tokenizeCard(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'checkout_id' => 'required|exists:billing_information,payment_checkout_id',
            'number' => 'required|string',
            'holder' => 'required|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:4',
            'cvv' => 'required|string',
            'payment_method' => 'required|in:VISA,MASTER,MADA'
        ]);

        // Extract card data and payment method from the request
        $cardData = $request->only(['number', 'holder', 'expiry_month', 'expiry_year', 'cvv']);
        $paymentMethod = $request->input('payment_method');

        try {
            // Tokenize the card using HyperPay service
            $tokenResponse = $this->hyperPayService->tokenizeCard($cardData, $paymentMethod);
            // dd($tokenResponse);

            // Update the billing information with the card number
            $billingInformation = BillingInformation::where('user_id', Auth::id())
                ->where('payment_checkout_id', $request->checkout_id)
                ->first();

            if (!$billingInformation) {
                return response()->json([
                    'error' => 'Billing information not found',
                    'error_ar' => 'لم يتم العثور على معلومات الفوترة',
                ], 404);
            }

            $billingInformation->update([
                'card_number' => $request->number,
            ]);

            // Return the tokenization response
            return response()->json($tokenResponse);
        } catch (\Exception $e) {
            // Handle the error thrown by the service
            return response()->json([
                'error' => 'Failed to tokenize card',
                'error_ar' => 'فشل ترميز البطاقة',
                'message' => $e->getMessage()
            ], 400);
        }
    }



    /**
     * Check Card Balance (Preauthorization)
     */
    // public function checkBalance(Request $request)
    // {
    //     $request->validate([
    //         'token' => 'required|string',
    //     ]);

    //     $response = $this->hyperPayService->checkCardBalance($request->input('token'));

    //     return response()->json($response);
    // }

    public function checkBalance(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $response = $this->hyperPayService->checkCardBalance($request->input('token'));

        if (isset($response['error'])) {
            return response()->json($response, 400); // Return 400 for errors
        }

        return response()->json([
            'balance' => $response,
        ], 200); // Return 200 for success
    }
}
