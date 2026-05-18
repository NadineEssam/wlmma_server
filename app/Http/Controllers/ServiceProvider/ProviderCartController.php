<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
// use App\Models\CommercialTooAttribute;
// use App\Models\CommercialTooAttributeValue;
use App\Models\CommercialTool;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SuppliesRent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProviderCartController extends Controller
{
    /**
     * Retrieve or create a cart ID.
     */
    protected function getCartId(Request $request)
    {
        return Cart::query()
            ->when(auth()->check(), function ($query) {
                $query->where('user_id', auth()->id());
            }, function ($query) use ($request) {
                $query->where('device_id', $request->header('device_id'));
            })
            ->firstOrFail()
            ->id;
    }

    /**
     * Add an item to the cart.
     */
    // ORIGIN
    // public function addToCart(Request $request)
    // {
    //     // Validation rules for multiple tools
    //     $validator = Validator::make($request->all(), [
    //         // 'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
    //         // 'tools.*.attribute_id' => 'required|integer|exists:commercial_too_attribute_values,id',
    //         'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
    //         'tools.*.attribute_id' => [
    //             'required',
    //             'integer',
    //             function ($attribute, $value, $fail) use ($request) {
    //                 $index = explode('.', $attribute)[1]; // Extract index from "tools.X.attribute_id"
    //                 $toolId = $request->tools[$index]['tool_id']; // Get related tool_id
    //                 $exists = DB::table('commercial_too_attribute_values')
    //                     ->join('commercial_too_attributes', 'commercial_too_attribute_values.tool_attribute_id', '=', 'commercial_too_attributes.id')
    //                     ->where('commercial_too_attribute_values.id', $value)
    //                     ->where('commercial_too_attributes.tool_id', $toolId) // Ensure correct relation
    //                     ->exists();
    //                 if (!$exists) {
    //                     $fail("The selected attribute_id ({$value}) is not related to the given tool_id ({$toolId}).");
    //                 }
    //             },
    //         ],
    //         'tools.*.quantity' => 'required|integer|min:1',
    //     ]);
    //     // Validate the request
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }
    //     // Determine user_id properly
    //     $user_id = auth()->check() ? auth()->id() : null;
    //     // Fetch or create the user's cart
    //     $cart = Cart::firstOrCreate(
    //         ['user_id' => $user_id],
    //         ['device_id' => $request->header('device-id')] // For guest carts, identify by device ID
    //     );
    //     // Loop through the tools array and add each to the cart_items table
    //     $total_price_last = 0;
    //     $total_price = 0;
    //     $old_total_price = Cart::where('id', $cart->id)->first()->total_price;
    //     // dd($old_total_price);
    //     foreach ($request->tools as $item) {
    //         $all = CommercialTool::with([
    //             'commercialAttribute',
    //             'commercialAttribute.commercialToolAttributeValues'
    //         ])->find($item['tool_id']);
    //         // Check if relationships exist before accessing
    //         // if ($all && $all->commercialAttribute && $all->commercialAttribute->commercialToolAttributeValues->isNotEmpty()) {
    //         $price = $all->commercialAttribute->commercialToolAttributeValues->first()->price; // Get price of the first record
    //         // dd($price);
    //         // } else {
    //         //     dd('No price found');
    //         // }
    //         // $tools = CommercialTool::find($item['tool_id']);
    //         // $commercial_too_attribute_values = CommercialTooAttributeValue::find($item['attribute_id']);
    //         // dd($commercial_too_attribute_values);
    //         // dd($all);
    //         $total_price += $price * $item['quantity'];
    //         // dd($total_price);
    //         // Check if the tool is already in the cart_items table for this user's cart
    //         $existingCartItem = CartItem::where('cart_id', $cart->id)
    //             ->where('tool_id', $item['tool_id'])
    //             ->first();
    //         // dd($existingCartItem);
    //         if ($existingCartItem) {
    //             // If already exists, update the quantity
    //             $existingCartItem->quantity = $item['quantity'];
    //             // $existingCartItem->quantity += $item['quantity']; // OLD
    //             $existingCartItem->save();
    //         } else {
    //             // Else, create a new entry
    //             CartItem::create([
    //                 'cart_id' => $cart->id,
    //                 'tool_id' => $item['tool_id'],
    //                 'attribute_id' => $item['attribute_id'],
    //                 'quantity' => $item['quantity'],
    //             ]);
    //         }
    //     }
    //     // Update cart total price **after** looping through all items
    //     // $old_total_price = $total_price;
    //     $cart->update(['old_price' => $old_total_price]);
    //     $total_price_last = $total_price + $old_total_price;
    //     $cart->update(['total_price' => $total_price_last]);
    //     // dd($total_price_last);
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Tools added to cart successfully',
    //         'message_ar' => 'تمت إضافة الأدوات إلى سلة التسوق بنجاح',
    //         'cart' => $cart,
    //     ], 200);
    // }
    public function addToCart(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
            'tools.*.quantity' => 'required|integer|min:1',
            'tools.*.status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Determine user or device ID
        $user_id = auth()->check() ? auth()->id() : null;
        $device_id = $request->header('device-id');

        // Get or create cart
        $cart = Cart::firstOrCreate(
            ['user_id' => $user_id, 'device_id' => $device_id],
            ['total_price' => 0]
        );

        foreach ($request->tools as $item) {
            $tool = CommercialTool::find($item['tool_id']);
            $new_quantity = $item['quantity'];
            $status = $item['status'];
            $total_price = $item['quantity'] * $tool->price;
            // echo 'Status '.$status.' ';
            // echo 'new_quantity '.$new_quantity ;

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('tool_id', $item['tool_id'])
                ->first();

            if ($cartItem) {
                if ($status === 'add') {
                    $cartItem->quantity += $new_quantity;
                } else if ($status === 'minus') {
                    $cartItem->quantity -= $new_quantity;
                } else {
                    if ($cartItem->quantity <= 0) {
                        $cartItem->delete();
                        continue;  // skip saving
                    }
                }
                // Update existing quantity
                $cartItem->save();
            } else {
                // Create new cart item
                CartItem::create([
                    'cart_id' => $cart->id,
                    'tool_id' => $item['tool_id'],
                    'quantity' => $new_quantity,
                ]);
            }
            // SuppliesRent::create(
            //     [
            //         'renter_id' => $user_id,
            //         'supplier_id' => $tool->user_id,
            //         'tool_id' => $item['tool_id'],
            //         'quantity' => $new_quantity,
            //         'price' => $total_price,
            //     ]
            // );
        }

        // ✅ Recalculate total price from all cart items
        $total_price = 0;
        $all_cart_items = CartItem::where('cart_id', $cart->id)->get();

        foreach ($all_cart_items as $item) {
            $tool = CommercialTool::find($item->tool_id);
            $total_price += $tool->price * $item->quantity;
        }

        // ✅ Update the cart with the correct total
        $cart->update([
            'old_price' => 0,
            'total_price' => $total_price,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tools added to cart successfully',
            'message_ar' => 'تمت إضافة الأدوات إلى سلة التسوق بنجاح',
            'cart' => $cart->load('cartItems.tool'),  // Optional: eager load items
        ], 200);
    }

    /**
     * Show Cart items.
     */
    public function showCart(Request $request)
    {
        // Determine user_id and device_id
        $user_id = auth()->check() ? auth()->id() : null;
        $device_id = $request->header('device-id') ?? null;

        // Fetch the cart for authenticated user or guest user using device_id
        $cart = null;

        if ($user_id) {
            // For authenticated users, find their cart
            $cart = Cart::where('user_id', $user_id) /* ->where('id',$request->cart_id) */ ->first();
        } elseif ($device_id) {
            // For guests, find their cart using device_id
            $cart = Cart::where('device_id', $device_id) /* ->where('id',$request->cart_id) */ ->first();
        }

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'No cart found',
                'message_ar' => 'لم يتم العثور على عربة',
            ], 404);
        }

        // Fetch all the cart items along with the commercial_tool_types
        // $cartItems = CartItem::with(['tool.type','tool.commercialAttribute','tool.commercialAttribute.commercialToolAttributeValues'])
        //     ->join('commercial_too_attribute_values', 'commercial_too_attribute_values.id', '=', 'cart_items.attribute_id')
        //     ->where('cart_id', $cart->id)
        //     ->orderBy('tool_id', 'desc')
        //     ->get();

        $cartItems = CartItem::with([
            'tool.type',
            // 'tool.commercialAttribute',
            'tool.toolImages'  // ,
            // 'tool.commercialAttribute.commercialToolAttributeValues' => function ($query) use (&$cartItemIds) {
            //     $query->whereIn('id', function ($subQuery) {
            //         $subQuery
            //             ->select('attribute_id')
            //             ->from('cart_items')
            //             ->whereColumn('cart_items.attribute_id', 'commercial_too_attribute_values.id');
            //     });
            // }
        ])
            ->where('cart_id', $cart->id)
            ->orderBy('tool_id', 'desc')
            ->get();

        $cartItems->transform(function ($cartItem) {
            if ($cartItem->tool && $cartItem->tool->toolImages) {
                $cartItem->tool->tool_images = $cartItem->tool->toolImages->map(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/' . $image->image_path;
                    return $image;
                });
            }
            return $cartItem;
        });

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'cart_items' => $cartItems,
        ], 200);
    }

    /**
     * Remove an item from the cart.
     */
    // public function removeFromCart(Request $request)
    // {
    //     // Validate the request
    //     $request->validate([
    //         'tool_id' => 'required|exists:commercial_tools,id',  // Assuming tools are in 'commercial_tools' table
    //     ]);
    //     // Determine the cart ID based on user or device
    //     $cart = Cart::query()
    //         ->when(auth()->check(), function ($query) {
    //             $query->where('user_id', auth()->id());
    //         }, function ($query) use ($request) {
    //             $query->where('device_id', $request->header('device_id'));  // Device ID from headers
    //         })
    //         ->first();
    //     if (!$cart) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Cart not found.',
    //             'message_ar' => 'لم يتم العثور على عربة',
    //         ], 404);
    //     }
    //     // Attempt to remove the tool from the cart
    //     $deleted = CartItem::where('cart_id', $cart->id)
    //         ->where('tool_id', $request->tool_id)
    //         ->delete();
    //     $tool = CommercialTool::find($request->tool_id);
    //     $tool_attribute_id = CommercialTooAttribute::where('tool_id', $request->tool_id)->first()->id;
    //     $commercial_too_attribute_values = CommercialTooAttributeValue::where('tool_attribute_id', $tool_attribute_id)->first()->price;
    //     // dd($commercial_too_attribute_values);
    //     $last_total_price = $cart->total_price - $commercial_too_attribute_values;
    //     $cart->update(['total_price' => $last_total_price]);
    //     if ($deleted) {
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Item removed from cart.',
    //             'message_ar' => 'تمت إزالة العنصر من سلة التسوق.',
    //         ], 200);
    //     }
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Item not found in cart.',
    //         'message_ar' => 'لم يتم العثور على العنصر في سلة التسوق.',
    //     ], 404);
    // }
    public function removeFromCart(Request $request)
    {
        // Validate the request
        $request->validate([
            'tool_id' => 'required|exists:commercial_tools,id',
        ]);

        // Get the user's cart
        $cart = Cart::query()
            ->when(auth()->check(), function ($query) {
                $query->where('user_id', auth()->id());
            }, function ($query) use ($request) {
                $query->where('device_id', $request->header('device_id'));
            })
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found.',
                'message_ar' => 'لم يتم العثور على عربة',
            ], 404);
        }

        // Get the cart item to be deleted
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('tool_id', $request->tool_id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart.',
                'message_ar' => 'لم يتم العثور على العنصر في سلة التسوق.',
            ], 404);
        }

        // Get tool price
        $tool = CommercialTool::find($request->tool_id);

        // Recalculate total price
        $deducted_price = $tool->price * $cartItem->quantity;
        $new_total_price = max(0, $cart->total_price - $deducted_price);  // prevent negative

        // Delete item from cart
        $cartItem->delete();

        // Update cart total
        $cart->update(['total_price' => $new_total_price]);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'message_ar' => 'تمت إزالة العنصر من سلة التسوق.',
        ], 200);
    }

    /**
     * Clear all items in the cart.
     */
    public function clearCart(Request $request)
    {
        $cartId = $this->getCartId($request);

        CartItem::where('cart_id', $cartId)->delete();
        $cart = Cart::find($cartId);
        $cart->update(['total_price' => 0]);

        return response()->json([
            'message' => 'Cart cleared successfully.',
            'message_ar' => 'تم مسح سلة التسوق بنجاح.',
        ], 200);
    }

    public function checkout(Request $request)
    {
        $cartId = $this->getCartId($request);

        // Fetch cart items
        $cartItems = CartItem::with('tool')->where('cart_id', $cartId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty.',
                'message_ar' => 'العربة فارغة.'
            ], 400);
        }

        // Calculate total price
        $total = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->tool->price * $item->quantity);
        }, 0);

        // Create Order (example logic)
        $order = Order::create([
            'user_id' => auth()->id() ?? null,
            'total' => $total,
            'status' => 'pending',
        ]);

        // Attach cart items to the order
        foreach ($cartItems as $item) {
            $order->orderItems()->create([
                'tool_id' => $item->tool_id,
                'quantity' => $item->quantity,
                'price' => $item->tool->price,
            ]);
        }

        // Clear the cart after checkout
        CartItem::where('cart_id', $cartId)->delete();

        return response()->json([
            'message' => 'Checkout successful.',
            'message_ar' => 'تمت العملية بنجاح',
            'order_id' => $order->id,
            'total' => $total,
        ], 200);
    }
}
