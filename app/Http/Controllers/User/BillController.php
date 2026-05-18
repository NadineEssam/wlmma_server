<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BillingInformation;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BillController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $billData = BillingInformation::where('user_id', $user->id)->get();
        $walletData = Wallet::with(['transactions.cart.cartItems.tool', 'transactions.booking.activity.serviceprovider'])->where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'bills' => $billData,
            'wallet' => $walletData,
        ], 200);
    }
}
