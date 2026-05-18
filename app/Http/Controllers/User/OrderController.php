<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Models\CommercialTool;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status_id = $request->input('status_id');
        $orders = Order::with(['orderItems', 'orderItems.tool', 'orderItems.tool.toolImages'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');
        
        if ($status_id == 0) {
            // Previous: all order items have ended before today
            $orders->whereHas('orderItems', function ($query) {
                $query->where('created_at', '<', now()->toDateString());
            });
        }
        
        if ($status_id == 1) {
            // Current & upcoming: at least one order item ends today or in the future
            $orders->whereHas('orderItems', function ($query) {
                $query->where('created_at', '>=', now()->toDateString());
            });
        }

        $orders = $orders->paginate($perPage);

        $orders->transform(function ($order) {
            $order->orderItems->transform(function ($item) {
                // ✅ sub_total for each item
                $item->sub_total = $item->quantity * $item->price;
                if ($item->tool && $item->tool->toolImages) {
                    $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
                        // $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                            $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        }
                        return $image;
                    });
                }
                return $item;
            });
            return $order;
        });

        return response()->json([
            'message' => true,
            'data' => $orders
        ],
            200);
    }

    public function show($order_id)
    {
        $order = Order::with(['user', 'orderItems', 'orderItems.tool', 'orderItems.tool.toolImages'])
            ->where('user_id', auth()->id())
            ->find($order_id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'message_ar' => 'الطلب غير موجود',
            ], 404);
        }

        // Transform order items
        $order->orderItems->transform(function ($item) {
            // ✅ Calculate sub_total for each item
            $item->sub_total = $item->quantity * $item->price;

            if ($item->tool && $item->tool->toolImages) {
                $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
                    if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    }
                    return $image;
                });
            }

            return $item;
        });

        return response()->json([
            'message' => true,
            'data' => $order
        ], 200);
    }

    public function indexProvider(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status_id = $request->input('status_id');

        $orders = Order::with([
            'orderItems' => function ($query) {
                $query->whereHas('tool', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            },
            'orderItems.tool.toolImages'
        ])
            ->whereHas('orderItems.tool', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('created_at', 'desc');

        if ($status_id == 0) {
            $orders->where('created_at', '<', now()->startOfDay());
        } elseif ($status_id == 1) {
            $orders->where('created_at', '>=', now()->startOfDay());
        }

        $orders = $orders->paginate($perPage);

        $orders->getCollection()->transform(function ($order) {
            $order->orderItems->transform(function ($item) {
                $item->sub_total = $item->quantity * $item->price;

                if ($item->tool && $item->tool->toolImages) {
                    $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
                        if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                            $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        }
                        return $image;
                    });
                }

                return $item;
            });

            return $order;
        });

        return response()->json([
            'message' => true,
            'data' => $orders
        ], 200);
    }

    public function create(Request $request)
    {
        $order = Order::create([
            'user_id' => $request->user_id,
            'total' => $request->total,
            'status' => 'pending',
        ]);
        $orderTotal = $request->total;
        // $amount = ccc;
        // $cashback = floor($orderTotal / 500) * $amount;
        $code_cashback = Cashback::first();
        $order_spent = $code_cashback->order_spent;
        $order_cashback = $code_cashback->order_cashback;
        if ($orderTotal == $order_spent) {
            $cashback = floor($orderTotal / $order_spent) * $order_cashback;
            if ($cashback > 0) {
                $wallet = Wallet::firstOrCreate(['user_id' => $request->user_id]);
                $wallet->increment('balance', $cashback);
            }
        }
        return response()->json($order, 201);
    }

    public function addToOrderFromCart(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'additional_details' => 'nullable|string',
            'shortNationalAddress' => 'nullable|string',
            'province' => 'nullable|string',
            'cityGovernorate' => 'nullable|string',
            'location' => 'nullable|string',
            'tools.*.tool_id' => 'required|integer|exists:commercial_tools,id',
            'tools.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Determine user or device ID
        $user_id = auth()->check() ? auth()->id() : null;

        // Get or create order
        $order = Order::create([
            'user_id' => $user_id,
            'total' => 0,  // will update this later after adding items
            'lat' => $request->lat,
            'long' => $request->long,
            'location' => $request->location,
            'additional_details' => $request->additional_details,
            'shortNationalAddress' => $request->shortNationalAddress,
            'province' => $request->province,
            'cityGovernorate' => $request->cityGovernorate,
        ]);

        // dd($order);
        foreach ($request->tools as $item) {
            $tool = CommercialTool::find($item['tool_id']);
            $quantity = $item['quantity'];

            $orderItem = orderItem::where('order_id', $order->id)
                ->where('tool_id', $item['tool_id'])
                ->first();

            if ($orderItem) {
                // Update existing quantity
                $orderItem->quantity += $quantity;
                $orderItem->save();
            } else {
                // Create new order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'price' => $tool->price,
                    'tool_id' => $item['tool_id'],
                    'quantity' => $quantity,
                ]);
            }
        }

        // Recalculate total price from all order items
        $total_price = 0;
        $all_order_items = OrderItem::where('order_id', $order->id)->get();

        foreach ($all_order_items as $item) {
            $tool = CommercialTool::find($item->tool_id);
            $total_price += $tool->price * $item->quantity;
        }

        // Update the order with the correct total
        $order->update([
            'old_price' => 0,
            'total' => $total_price,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tools added to order successfully',
            'message_ar' => 'تمت إضافة الأدوات إلى الطلب بنجاح',
            'order' => $order->load('orderItems.tool', 'orderItems.tool.toolImages'),  // Optional: eager load items
        ], 200);
    }

    public function updateStatus(Request $request, $order)
    {
        $order = Order::findOrFail($order);
        $order->status = $request->status;
        $order->save();

        return response()->json($order);
    }
}
