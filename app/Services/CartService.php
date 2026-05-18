<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;

class CartService
{
    public function clearCart($cartId)
    {
        CartItem::where('cart_id', $cartId)->delete();
        $cart = Cart::find($cartId);
        $cart->update(['total_price' => 0]);

        return response()->json([
            'message' => 'Cart cleared successfully.',
            'message_ar' => 'تم مسح سلة التسوق بنجاح.',
        ], 200);
    }
}
