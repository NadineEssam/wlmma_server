<?php

namespace App\Services;

use App\Models\Wallet;

class WalletService
{
    // balance increase
    public function credit($userId, $book_id = null, $cart_id = null, $amount, $reference = null, $reason = null)
    {
        $wallet = Wallet::where(['user_id' => $userId])->first();
        $wallet->increment('balance', $amount);

        $wallet->transactions()->create([
            'type' => 'credit',
            'user_id' => $userId,
            'book_id' => $book_id,
            'cart_id' => $cart_id,
            'amount' => $amount,
            'reference' => $reference,
            'reason' => $reason,
        ]);
    }

    // balance decrease
    public function debit($userId, $book_id = null, $cart_id = null, $amount, $reference = null, $reason = null)
    {
        $wallet = Wallet::where(['user_id' => $userId])->first();

        // if ($wallet->balance < $amount) {
        //     throw new \Exception('Insufficient wallet balance');
        // }

        $wallet->decrement('balance', $amount);

        $wallet->transactions()->create([
            'type' => 'debit',
            'user_id' => $userId,
            'book_id' => $book_id,
            'cart_id' => $cart_id,
            'amount' => $amount,
            'reference' => $reference,
            'reason' => $reason,
        ]);
    }
}
