<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function create(Request $request)
    {
        $orderItem = OrderItem::create([
            'order_id' => $request->order_id,
            'tool_id' => $request->tool_id,
            'quantity' => $request->quantity,
        ]);

        return response()->json($orderItem, 201);
    }

    public function show($order_id)
    {
        $orderItems = OrderItem::where('order_id', $order_id)->get();
        return response()->json($orderItems);
    }
}
