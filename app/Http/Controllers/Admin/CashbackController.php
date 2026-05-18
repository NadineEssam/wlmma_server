<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cashback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashbackController extends Controller
{
    // Setting details
    public function index()
    {
        // Eager load relationships to avoid N+1 queries
        $cashback = Cashback::get();
        return response()->json([
            'message' => 'Success',
            'data' => $cashback,
        ], 200);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'campaign' => 'nullable|string|in:yes,no',
            'campaign_amount' => 'required_if:campaign,yes|numeric',
            'order_spent' => 'nullable|numeric',
            'order_cashback' => 'nullable|string',
            'code_cashback' => 'nullable|string',
        ]);

        // $setting = DB::transaction(function () use ($validatedData) {
        //     return Cashback::create([
        //         'campaign' => $validatedData['campaign'] ?? 'no',
        //         'campaign_amount' => $validatedData['campaign_amount'] ?? 0,
        //         'order_spent' => $validatedData['order_spent'] ?? 500,
        //         'order_cashback' => $validatedData['order_cashback'] ?? 50,
        //         'code_cashback' => $validatedData['code_cashback'] ?? 20,
        //     ]);
        // });

        $setting = DB::transaction(function () use ($validatedData) {
            $cashback = Cashback::first();  // Get existing record

            if ($cashback) {
                // ✅ Apply new values if provided
                $cashback->update([
                    'campaign' => $validatedData['campaign'] ?? $cashback->campaign,
                    'campaign_amount' => $validatedData['campaign_amount'] ?? $cashback->campaign_amount,
                    'order_spent' => $validatedData['order_spent'] ?? $cashback->order_spent,
                    'order_cashback' => $validatedData['order_cashback'] ?? $cashback->order_cashback,
                    'code_cashback' => $validatedData['code_cashback'] ?? $cashback->code_cashback,
                ]);
            } else {
                // ✅ Create new record if none exists
                $cashback = Cashback::create([
                    'campaign' => $validatedData['campaign'] ?? 'no',
                    'campaign_amount' => $validatedData['campaign_amount'] ?? 0,
                    'order_spent' => $validatedData['order_spent'] ?? 500,
                    'order_cashback' => $validatedData['order_cashback'] ?? 50,
                    'code_cashback' => $validatedData['code_cashback'] ?? 20,
                ]);
            }

            return $cashback;
        });

        // ✅ Return response after transaction is committed
        return response()->json([
            'message' => 'Success',
            'message_ar' => 'تم بنجاح',
            'data' => $setting,
        ], 200);
    }
}
