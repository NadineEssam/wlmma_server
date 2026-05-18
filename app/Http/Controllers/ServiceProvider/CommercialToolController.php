<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommercialTool\StoreCommercialToolRequest;
use App\Http\Requests\CommercialTool\UpdateCommercialToolRequest;
use App\Http\Resources\CommercialToolResource;
// use App\Models\CommercialTooAttribute;
// use App\Models\CommercialTooAttributeValue;
use App\Models\CommercialTool;
use App\Models\CommercialToolImage;
use App\Models\CommercialToolType;
use App\Models\Image;
use App\Models\Order;
use App\Models\SuppliesRent;
use App\Services\CommercialTool\CommercialToolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommercialToolController extends Controller
{
    public function __construct(
        public CommercialToolService $service
    ) {}

    public function index(Request $request)
    {
        $tools = CommercialTool::with([/* 'commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', */ 'user.userType', 'type', 'toolImages'])
            ->where('user_id', $request->user()->id)
            ->paginate($request->per_page ?: 15);

        return CommercialToolResource::collection($tools);
    }

    public function suppliesRentforProvider(Request $request)
    {
        $per_page = $request->query('per_page', 15);  // 👈 هنا الصح

        $tools = SuppliesRent::with(['supplier', 'tool.type', 'tool.toolImages'])
            ->where('renter_id', auth()->id())
            ->paginate($per_page);

        $tools->getCollection()->transform(function ($rent) {
            if ($rent->tool && $rent->tool->toolImages) {
                $rent->tool->toolImages->transform(function ($image) {
                    $image->image_path = config('app.url') . '/storage/' . ltrim($image->image_path, '/');
                    return $image;
                });
            }
            return $rent;
        });

        if ($tools->isEmpty()) {
            return response()->json([
                'message' => 'Empty',
                'message_ar' => 'فارغ',
                'data' => [],
            ]);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $tools->items(),
            'pagination' => [
                'current_page' => $tools->currentPage(),
                'last_page' => $tools->lastPage(),
                'per_page' => $tools->perPage(),
                'total' => $tools->total(),
            ],
        ]);
    }

    public function forRent(Request $request)
    {
        $tools = CommercialTool::with([/* 'commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', */ 'user', 'type', 'toolImages'])
            ->where('user_id', $request->user()->id)
            ->where('type_id', 1)
            ->paginate($request->per_page ?: 15);

        return CommercialToolResource::collection($tools);
    }

    public function forSale(Request $request)
    {
        $tools = CommercialTool::with([/* 'commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', */ 'user', 'type', 'toolImages'])
            ->where('user_id', $request->user()->id)
            ->where('type_id', 2)
            ->paginate($request->per_page ?: 15);

        return CommercialToolResource::collection($tools);
    }

    public function forRentFromSuppliers(Request $request)
    {
        $tools = CommercialTool::with([/* 'commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', */ 'user', 'user.userType', 'type', 'toolImages'])
            ->where('user_id', '<>', $request->user()->id)
            ->where('type_id', 3)
            ->paginate($request->per_page ?: 15);

        return CommercialToolResource::collection($tools);
    }

    public function store(StoreCommercialToolRequest $request)
    {
        $tool = $this->service->createCommercialTool($request->all(), $request->file('images'), $request->user()->id);
        // dd($tool);
        // return new CommercialToolResource($tool);
        // return $tool;
        return response()->json([
            'message' => 'Tool created successfully',
            'message_ar' => 'تم إنشاء الأداة بنجاح',
            'data' => $tool,
        ]);
    }
    public function getAllSuppliersTools(Request $request)
{
    $perPage = $request->per_page ?? 15;

    $tools = CommercialTool::with([
        'user',
        'user.userType',
        'type',
        'toolImages'
    ])
    ->where('type_id', 3)
    ->paginate($perPage);

    // Full image URL
    $tools->getCollection()->transform(function ($tool) {

        if ($tool->toolImages) {
            $tool->toolImages->transform(function ($image) {

                $image->image_path = env('APP_URL') . 'storage/' . ltrim($image->image_path, '/');

                return $image;
            });
        }

        return $tool;
    });

    return response()->json([
        'message' => 'Suppliers tools retrieved successfully',
        'message_ar' => 'تم استرجاع أدوات الموردين بنجاح',
        'data' => CommercialToolResource::collection($tools),
        'pagination' => [
            'current_page' => $tools->currentPage(),
            'last_page' => $tools->lastPage(),
            'per_page' => $tools->perPage(),
            'total' => $tools->total(),
        ],
    ]);
}

public function getAllSuppliersToolsForProvider(Request $request)
{
    $perPage = $request->per_page ?? 15;

    // priority: request provider_id → fallback to token user
    $providerId = $request->provider_id ?? $request->user()->id;

    $tools = CommercialTool::with([
        'user',
        'user.userType',
        'type',
        'toolImages'
    ])
    ->where('type_id', 3)
    ->where('user_id', $providerId)
    ->paginate($perPage);

    $tools->getCollection()->transform(function ($tool) {
        if ($tool->toolImages) {
            $tool->toolImages->transform(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/' . ltrim($image->image_path, '/');
                return $image;
            });
        }
        return $tool;
    });

    return response()->json([
        'message' => 'Supplier tools retrieved successfully',
        'message_ar' => 'تم استرجاع أدوات المورد بنجاح',
        'data' => CommercialToolResource::collection($tools),
        'pagination' => [
            'current_page' => $tools->currentPage(),
            'last_page' => $tools->lastPage(),
            'per_page' => $tools->perPage(),
            'total' => $tools->total(),
        ],
    ]);
}

    

    // public function update(UpdateCommercialToolRequest $request, $toolId)
    // {
    //     // Find the CommercialTool or fail
    //     $toolModel = CommercialTool::findOrFail($toolId);

    //     Log::info('Request payload:', $request->all());

    //     // Use a database transaction to ensure atomicity
    //     DB::transaction(function () use ($request, $toolModel) {
    //         // Update the CommercialTool attributes
    //         $toolModel->update([
    //             'name_en' => $request->name_en,
    //             'name_ar' => $request->name_ar,
    //             'description_en' => $request->description_en,
    //             'description_ar' => $request->description_ar,
    //             'type_id' => $request->tool_type_id,
    //         ]);

    //         // Handle tool attributes
    //         if ($request->has('tool_attributes')) {
    //             // Find or create the tool attribute
    //             $toolAttribute = CommercialTooAttribute::where('tool_id', $toolModel->id)->first();
    //             Log::info('Tool Attribute ID:', ['id' => $toolAttribute->id]);

    //             if ($toolAttribute) {
    //                 $toolAttribute->update([
    //                     'attribute_name_en' => $request->tool_attributes['attribute_name_en'],
    //                     'attribute_name_ar' => $request->tool_attributes['attribute_name_ar'],
    //                 ]);
    //             } else {
    //                 $toolAttribute = CommercialTooAttribute::create([
    //                     'tool_id' => $toolModel->id,
    //                     'attribute_name_en' => $request->tool_attributes['attribute_name_en'],
    //                     'attribute_name_ar' => $request->tool_attributes['attribute_name_ar'],
    //                 ]);
    //             }

    //             Log::info('Tool Attribute:', ['toolAttribute' => $toolAttribute]);

    //             // Update or create tool attribute values
    //             if (!empty($request->tool_attributes['values'])) {
    //                 $toolAttributeId = $toolAttribute->id;

    //                 // Log the toolAttributeId
    //                 Log::info('Tool Attribute ID for deletion:', ['tool_attribute_id' => $toolAttributeId]);

    //                 // Step 1: Delete existing values for the given tool_attribute_id
    //                 CommercialTooAttributeValue::where('tool_attribute_id', $toolAttributeId)->delete();

    //                 // Step 2: Insert new values
    //                 $newValues = [];
    //                 foreach ($request->tool_attributes['values'] as $value) {
    //                     Log::info('Processing Value:', [
    //                         'tool_attribute_id' => $toolAttributeId,
    //                         'value' => $value['value'],
    //                         'price' => $value['price'] ?? null,
    //                     ]);

    //                     $newValues[] = [
    //                         'tool_attribute_id' => $toolAttributeId,
    //                         'value' => $value['value'],
    //                         'price' => $value['price'] ?? null,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];
    //                 }

    //                 // Bulk insert new values
    //                 CommercialTooAttributeValue::insert($newValues);
    //             }
    //         }

    //         // Handle uploaded images
    //         // Delete existing images and related pivot table entries
    //         $toolModel->toolImages()->delete();
    //         // Image::whereIn('id', $toolModel->toolImages()->pluck('id'))->delete();
    //         Image::whereIn('images.id', $toolModel->toolImages()->pluck('commercial_tool_images.image_id'))->delete();

    //         // Process new image uploads
    //         if ($request->hasFile('images')) {
    //             $images = $request->file('images');
    //             $imagePaths = [];

    //             foreach ($images as $image) {
    //                 $path = $image->store('commercialtools', 'public');
    //                 $imagePaths[] = [
    //                     'image_path' => $path,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
    //             }

    //             // Insert new images
    //             Image::insert($imagePaths);

    //             $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
    //             $ids = range($lastId - count($images) + 1, $lastId);

    //             $imageActivity = [];
    //             foreach ($ids as $id) {
    //                 $imageActivity[] = [
    //                     'image_id' => $id,
    //                     'commercial_tool_id' => $toolModel->id,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
    //             }

    //             CommercialToolImage::insert($imageActivity);
    //         }
    //     });

    //     // Reload the tool with related data
    //     $toolModel->load([
    //         'commercialAttribute',
    //         'commercialAttribute.commercialToolAttributeValues',
    //         'user',
    //         'type',
    //         'toolImages',
    //     ]);

    //     // Return success response
    //     return response()->json([
    //         'message' => 'Tool updated successfully',
    //         'message_ar' => 'تم تحديث الأداة بنجاح',
    //         'data' => $toolModel,
    //     ]);
    // }

    public function update(UpdateCommercialToolRequest $request, $toolId)
    {
        // Find the CommercialTool or fail
        $toolModel = CommercialTool::findOrFail($toolId);

        Log::info('Request payload:', $request->all());

        // Use a database transaction to ensure atomicity
        DB::transaction(function () use ($request, $toolModel) {
            // Update the CommercialTool attributes
            $toolModel->update([
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
                'description_en' => $request->description_en,
                'description_ar' => $request->description_ar,
                'type_id' => $request->tool_type_id,
            ]);

            // Handle tool attributes
            // if ($request->has('tool_attributes')) {
            //     // Find or create the tool attribute
            //     $toolAttribute = CommercialTooAttribute::where('tool_id', $toolModel->id)->first();
            //     Log::info('Tool Attribute ID:', ['id' => $toolAttribute->id]);

            //     if ($toolAttribute) {
            //         $toolAttribute->update([
            //             'attribute_name_en' => $request->tool_attributes['attribute_name_en'],
            //             'attribute_name_ar' => $request->tool_attributes['attribute_name_ar'],
            //         ]);
            //     } else {
            //         $toolAttribute = CommercialTooAttribute::create([
            //             'tool_id' => $toolModel->id,
            //             'attribute_name_en' => $request->tool_attributes['attribute_name_en'],
            //             'attribute_name_ar' => $request->tool_attributes['attribute_name_ar'],
            //         ]);
            //     }

            //     Log::info('Tool Attribute:', ['toolAttribute' => $toolAttribute]);

            //     // Update or create tool attribute values
            //     if (!empty($request->tool_attributes['values'])) {
            //         $toolAttributeId = $toolAttribute->id;

            //         // Log the toolAttributeId
            //         Log::info('Tool Attribute ID for deletion:', ['tool_attribute_id' => $toolAttributeId]);

            //         // Step 1: Delete existing values for the given tool_attribute_id
            //         CommercialTooAttributeValue::where('tool_attribute_id', $toolAttributeId)->delete();

            //         // Step 2: Insert new values
            //         $newValues = [];
            //         foreach ($request->tool_attributes['values'] as $value) {
            //             Log::info('Processing Value:', [
            //                 'tool_attribute_id' => $toolAttributeId,
            //                 'value' => $value['value'],
            //                 'price' => $value['price'] ?? null,
            //             ]);

            //             $newValues[] = [
            //                 'tool_attribute_id' => $toolAttributeId,
            //                 'value' => $value['value'],
            //                 'price' => $value['price'] ?? null,
            //                 'created_at' => now(),
            //                 'updated_at' => now(),
            //             ];
            //         }

            //         // Bulk insert new values
            //         CommercialTooAttributeValue::insert($newValues);
            //     }
            // }

            // Handle uploaded images
            // Delete existing images and related pivot table entries
            $toolModel->toolImages()->delete();
            // Image::whereIn('id', $toolModel->toolImages()->pluck('id'))->delete();
            Image::whereIn('images.id', $toolModel->toolImages()->pluck('commercial_tool_images.image_id'))->delete();

            // Process new image uploads
            if ($request->hasFile('images')) {
                // $images = $request->file('images');
                // $imagePaths = [];

                // foreach ($images as $image) {
                //     $path = $image->store('commercialtools', 'public');
                //     $imagePaths[] = [
                //         'image_path' => $path,
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ];
                // }

                // // Insert new images
                // Image::insert($imagePaths);

                // $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
                // $ids = range($lastId - count($images) + 1, $lastId);

                // $imageActivity = [];
                // foreach ($ids as $id) {
                //     $imageActivity[] = [
                //         'image_id' => $id,
                //         'commercial_tool_id' => $toolModel->id,
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ];
                // }

                // CommercialToolImage::insert($imageActivity);

                // Image Comperession
                // $images = $request->file('images');
                // $imagePaths = [];

                // foreach ($images as $image) {
                //     $filename = uniqid() . '.' . $image->getClientOriginalExtension();
                //     $destinationPath = storage_path('commercialtools/' . $filename);

                //     // Resize and compress image (without Intervention)
                //     list($width, $height) = getimagesize($image);
                //     $newWidth = 800;  // adjust width as needed
                //     $newHeight = intval($height * ($newWidth / $width));

                //     $sourceImage = match ($image->getClientOriginalExtension()) {
                //         'jpg', 'jpeg' => imagecreatefromjpeg($image),
                //         'png' => imagecreatefrompng($image),
                //         'webp' => imagecreatefromwebp($image),
                //         default => null,
                //     };

                //     if ($sourceImage) {
                //         $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                //         imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                //         // Save image to destination with compression
                //         switch ($image->getClientOriginalExtension()) {
                //             case 'jpg':
                //             case 'jpeg':
                //                 imagejpeg($resizedImage, $destinationPath, 75);  // 0 = worst, 100 = best quality
                //                 break;
                //             case 'png':
                //                 imagepng($resizedImage, $destinationPath, 6);  // 0 = no compression, 9 = max
                //                 break;
                //             case 'webp':
                //                 imagewebp($resizedImage, $destinationPath, 75);
                //                 break;
                //         }

                //         imagedestroy($sourceImage);
                //         imagedestroy($resizedImage);

                //         $path = 'commercialtools/' . $filename;

                //         $imagePaths[] = [
                //             'image_path' => $path,
                //             // 'created_at' => now(),
                //             'updated_at' => now(),
                //         ];
                //     }
                // }

                // Image::insert($imagePaths);

                // $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
                // $ids = range($lastId - count($imagePaths) + 1, $lastId);

                // $imageTool = [];
                // foreach ($ids as $id) {
                //     $imageTool[] = [
                //         'image_id' => $id,
                //         'commercial_tool_id' => $toolModel->id,
                //         // 'created_at' => now(),
                //         'updated_at' => now(),
                //     ];
                // }
                // CommercialToolImage::insert($imageTool);

                if ($request->hasFile('images')) {
                    $images = $request->file('images');
                    $imagePaths = [];

                    foreach ($images as $image) {
                        $filename = uniqid() . '.webp';
                        $destinationPath = storage_path('app/public/commercialtools/' . $filename);

                        // تأكد أن الفولدر موجود
                        if (!file_exists(dirname($destinationPath))) {
                            mkdir(dirname($destinationPath), 0755, true);
                        }

                        // المسار المؤقت للصورة
                        $imagePath = $image->getPathname();

                        // المقاسات الأصلية
                        [$width, $height] = getimagesize($imagePath);

                        // Resize لو أكبر من 1280
                        $newWidth = ($width > 1280) ? 1280 : $width;
                        $newHeight = intval($height * ($newWidth / $width));

                        // نوع الصورة
                        $extension = strtolower($image->getClientOriginalExtension());

                        $sourceImage = match ($extension) {
                            'jpg', 'jpeg' => imagecreatefromjpeg($imagePath),
                            'png' => imagecreatefrompng($imagePath),
                            default => null,
                        };

                        if (!$sourceImage) {
                            continue;
                        }

                        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                        // الحفاظ على الشفافية للـ PNG
                        if ($extension === 'png') {
                            imagealphablending($resizedImage, false);
                            imagesavealpha($resizedImage, true);
                            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                            imagefill($resizedImage, 0, 0, $transparent);
                        }

                        imagecopyresampled(
                            $resizedImage,
                            $sourceImage,
                            0, 0, 0, 0,
                            $newWidth,
                            $newHeight,
                            $width,
                            $height
                        );

                        // حفظ WebP بجودة 80%
                        imagewebp($resizedImage, $destinationPath, 80);

                        imagedestroy($sourceImage);
                        imagedestroy($resizedImage);

                        $imagePaths[] = [
                            'image_path' => 'commercialtools/' . $filename,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // حفظ الصور
                    Image::insert($imagePaths);

                    // ربط الصور بالـ tool
                    $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
                    $ids = range($lastId - count($imagePaths) + 1, $lastId);

                    $imageTool = [];

                    foreach ($ids as $id) {
                        $imageTool[] = [
                            'image_id' => $id,
                            'commercial_tool_id' => $toolModel->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    CommercialToolImage::insert($imageTool);
                }
            }
        });

        // Reload the tool with related data
        $toolModel->load([
            // 'commercialAttribute',
            // 'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
            'toolImages',
        ]);
        // Update image paths with the full URL
        $toolModel->toolImages->each(function ($image) {
            $image->image_path = env('APP_URL') . 'storage/' . $image->image_path;
        });

        // Return success response
        return response()->json([
            'message' => 'Tool updated successfully',
            'message_ar' => 'تم تحديث الأداة بنجاح',
            'data' => $toolModel,
        ]);
    }

    public function getToolTypes()
    {
        return CommercialToolType::select('id', 'name_ar', 'name_en')->get();
    }

    public function show(CommercialTool $tool)
    {
        $tool->load([/* 'commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', */ 'user', 'type', 'toolImages']);

        return new CommercialToolResource($tool);
    }

    public function destroy(Request $request, $id)
    {
        // try {
        $tool = CommercialTool::with([
            // 'commercialAttribute',
            // 'commercialAttribute.commercialToolAttributeValues',
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
                // $tool->toolImages()->delete();  // Delete associated images
                // $tool->commercialAttribute->commercialToolAttributeValues()->delete();  // Delete attribute values
                // $tool->commercialAttribute()->delete();  // Delete the attribute

                // Finally, delete the main tool
                $tool->delete();
            });

            return response()->json([
                'message' => 'Commercial tool have been deleted successfully',
                'message_ar' => 'تم حذف الأداة التجارية وخصائصها بنجاح',
                'is_deleted' => true,
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

    


}
