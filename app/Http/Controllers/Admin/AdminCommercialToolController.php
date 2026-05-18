<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommercialTool\StoreCommercialToolRequest;
use App\Http\Requests\CommercialTool\UpdateCommercialToolRequest;
use App\Http\Resources\CommercialToolResource;
use App\Models\CommercialTooAttribute;
use App\Models\CommercialTooAttributeValue;
use App\Models\CommercialTool;
use App\Models\CommercialToolImage;
use App\Models\CommercialToolType;
use App\Models\Image;
use App\Models\Order;
use App\Services\CommercialTool\CommercialToolService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminCommercialToolController extends Controller
{
    public function __construct(
        public CommercialToolService $service
    ) {}

    public function index(Request $request)
    {
        $tools = CommercialTool::with([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
            'toolImages'
        ]);

        // Paginate results
        $tools = $tools->orderBy('id', 'desc')->paginate($request->per_page ?: 15);

        // Transform the image_path using map
        $tools->getCollection()->transform(function ($tool) {
            // $tool->toolImages = $tool->toolImages->map(function ($image) { // old
            $tool->tool_images = $tool->toolImages->map(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            });
            return $tool;
        });

        return $tools;
    }

    public function forRent(Request $request)
    {
        $tools = CommercialTool::with(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user.userType', 'type', 'toolImages'])
            // ->where('user_id', $request->user()->id)
            ->where('type_id', 1)
            ->paginate($request->per_page ?: 15);

        return CommercialToolResource::collection($tools);
    }

    public function forSale(Request $request)
    {
        $tools = CommercialTool::with(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user.userType', 'type', 'toolImages'])
            // ->where('user_id', $request->user()->id)
            ->where('type_id', 2)
            ->paginate($request->per_page ?: 15);

        return CommercialToolResource::collection($tools);
    }

    // public function store(StoreCommercialToolRequest $request)
    // {
    //     $tool = $this->service->createCommercialTool($request->all(), $request->file('images'), $request->user()->id);

    //     return new CommercialToolResource($tool);
    // }

    public function store(StoreCommercialToolRequest $request)
    {
        $tool = $this->service->createCommercialToolForAdmin($request->all(), $request->file('images'), $request->provider_id);

        // return new CommercialToolResource($tool);
        // return $tool;
        return response()->json([
            'message' => 'Tool created successfully',
            'message_ar' => 'تم إنشاء الأداة بنجاح',
            'data' => $tool,
        ]);
    }

    public function update(Request $request, $toolId)
    {
        // Find the CommercialTool or fail
        $toolModel = CommercialTool::findOrFail($toolId);

        Log::info('Request payload:', $request->all());

        // Use a database transaction to ensure atomicity
        DB::transaction(function () use ($request, $toolModel) {
            // Update the CommercialTool attributes
            $toolModel->update([
                'name_en' => $request->name_en ?? $toolModel->name_en,
                'name_ar' => $request->name_ar ?? $toolModel->name_ar,
                'description_en' => $request->description_en ?? $toolModel->description_en,
                'description_ar' => $request->description_ar ?? $toolModel->description_ar,
                'type_id' => $request->tool_type_id ?? $toolModel->tool_type_id,
                'user_id' => $request->user_id ?? $toolModel->user_id,
            ]);

            // Handle tool attributes
            if ($request->has('tool_attributes')) {
                // Find or create the tool attribute
                $toolAttribute = CommercialTooAttribute::where('tool_id', $toolModel->id)->first();
                Log::info('Tool Attribute ID:', ['id' => $toolAttribute->id]);

                if ($toolAttribute) {
                    $toolAttribute->update([
                        'attribute_name_en' => $request->tool_attributes['attribute_name_en'] ?? $toolAttribute->attribute_name_en,
                        'attribute_name_ar' => $request->tool_attributes['attribute_name_ar'] ?? $toolAttribute->attribute_name_ar,
                    ]);
                } else {
                    $toolAttribute = CommercialTooAttribute::create([
                        'tool_id' => $toolModel->id,
                        'attribute_name_en' => $request->tool_attributes['attribute_name_en'],
                        'attribute_name_ar' => $request->tool_attributes['attribute_name_ar'],
                    ]);
                }

                Log::info('Tool Attribute:', ['toolAttribute' => $toolAttribute]);

                // Update or create tool attribute values
                if (!empty($request->tool_attributes['values'])) {
                    $toolAttributeId = $toolAttribute->id;

                    // Log the toolAttributeId
                    Log::info('Tool Attribute ID for deletion:', ['tool_attribute_id' => $toolAttributeId]);

                    // Step 1: Delete existing values for the given tool_attribute_id
                    CommercialTooAttributeValue::where('tool_attribute_id', $toolAttributeId)->delete();

                    // Step 2: Insert new values
                    $newValues = [];
                    foreach ($request->tool_attributes['values'] as $value) {
                        Log::info('Processing Value:', [
                            'tool_attribute_id' => $toolAttributeId,
                            'value' => $value['value'],
                            'price' => $value['price'] ?? null,
                        ]);

                        $newValues[] = [
                            'tool_attribute_id' => $toolAttributeId,
                            'value' => $value['value'],
                            'price' => $value['price'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Bulk insert new values
                    CommercialTooAttributeValue::insert($newValues);
                }
            }

            // Handle uploaded images
            // Delete existing images and related pivot table entries
            $toolModel->toolImages()->delete();
            // Image::whereIn('id', $toolModel->toolImages()->pluck('id'))->delete();
            Image::whereIn('images.id', $toolModel->toolImages()->pluck('commercial_tool_images.image_id'))->delete();

            // Process new image uploads
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $imagePaths = [];

                foreach ($images as $image) {
                    $path = $image->store('commercialtools', 'public');
                    $imagePaths[] = [
                        'image_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert new images
                Image::insert($imagePaths);

                $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
                $ids = range($lastId - count($images) + 1, $lastId);

                $imageActivity = [];
                foreach ($ids as $id) {
                    $imageActivity[] = [
                        'image_id' => $id,
                        'commercial_tool_id' => $toolModel->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                CommercialToolImage::insert($imageActivity);
            }
        });

        // Reload the tool with related data
        $toolModel->load([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
            'toolImages',
        ]);

        // Return success response
        return response()->json([
            'message' => 'Tool updated successfully',
            'message_ar' => 'تم تحديث الأداة بنجاح',
            'data' => $toolModel,
        ]);
    }

    public function getToolTypes()
    {
        $type = CommercialToolType::select('id', 'name_ar', 'name_en')->get();
        // $type = CommercialToolType::all();
        if (!$type) {
            return response()->json([
                'error' => 'CommercialToolType not found',
                'error_ar' => 'لم يتم العثور على نوع الأداة التجارية',
            ], 404);
        } else {
            return response()->json([
                'message' => 'Success',
                'message_ar' => 'نجاح',
                'data' => $type,
            ], 200);
        }
    }

    public function show(CommercialTool $tool)
    {
        $tool->load(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user.userType', 'type', 'toolImages']);

        return new CommercialToolResource($tool);
    }

    public function destroy(Request $request, $id)
    {
        // try {
        $tool = CommercialTool::with([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
            'toolImages'
        ])
            ->where('user_id', $request->user()->id)
            ->find($id);  // Find the tool by ID
        // dd($tool);
        if (!empty($tool)) {
            $orderd = Order::whereHas('orderItems', function ($q) use ($id) {
                $q->where('tool_id', $id);
            })
                ->with('orderItems')
                ->first();

            // dd($orderd);
            if ($orderd) {
                // Find the specific tool by ID for the authenticated user

                return response()->json([
                    'message' => 'Commercial tool has orders',
                    'message_ar' => 'هذه الأداة لديها طلبات',
                ], 200);
            }
            // Begin transaction for deleting the tool and its related models
            DB::transaction(function () use ($tool) {
                // Delete related models first to maintain referential integrity
                $tool->toolImages()->delete();  // Delete associated images
                // $tool->commercialAttribute->commercialToolAttributeValues()->delete();  // Delete attribute values
                $tool->commercialAttribute()->delete();  // Delete the attribute

                // Finally, delete the main tool
                $tool->delete();
            });

            return response()->json([
                'message' => 'Commercial tool and its attributes have been deleted successfully',
                'message_ar' => 'تم حذف الأداة التجارية وخصائصها بنجاح',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Not found',
                'message_ar' => 'البيانات ليست موجودة',
            ], 404);
        }
        // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        //     return response()->json([
        //         // 'message' => __('Commercial tool not found'),
        //         'message' => 'Commercial tool not found',
        //         'message_ar' => 'لم يتم العثور على الأداة التجارية',
        //     ], 404);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         // 'message' => __('Failed to delete tool'),
        //         'message' => 'Failed to delete tool',
        //         'message_ar' => 'فشل في حذف الأداة',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }
    }

    public function toolorders($tool_id)
    {
        $orders = Order::whereHas('orderItems', function ($query) use ($tool_id) {
            $query->where('tool_id', $tool_id);
        })
            ->with(['orderItems' => function ($query) use ($tool_id) {
                $query
                    ->where('tool_id', $tool_id)
                    ->with(['tool.toolImages']);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

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
        ], 200);
    }
}
