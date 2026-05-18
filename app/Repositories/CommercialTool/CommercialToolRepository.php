<?php

namespace App\Repositories\CommercialTool;

use App\Models\CommercialTooAttribute;
use App\Models\CommercialTooAttributeValue;
use App\Models\CommercialTool;
use App\Models\ToolAttributeValues;
use App\Models\Wishlist;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommercialToolRepository
{
    public function createCommercialTool($data)
    {
        $toolModels = [];

        DB::transaction(function () use ($data, &$toolModels) {
            foreach ($data['tool_types'] as $type) {
                $toolModel = CommercialTool::create([
                    'type_id' => $type['type_id'],
                    'price' => $type['price'],
                    'name_en' => $data['name_en'],
                    'name_ar' => $data['name_ar'],
                    'description_en' => $data['description_en'],
                    'description_ar' => $data['description_ar'],
                    'user_id' => $data['user_id'],
                ]);

                // Store attributes if available
                if (!empty($data['tool_attributes'])) {
                    $toolAttribute = CommercialTooAttribute::create([
                        'tool_id' => $toolModel->id,
                        'attribute_name_en' => $data['tool_attributes']['attribute_name_en'],
                        'attribute_name_ar' => $data['tool_attributes']['attribute_name_ar'],
                    ]);

                    $values = [];
                    foreach ($data['tool_attributes']['values'] as $value) {
                        $values[] = [
                            'tool_attribute_id' => $toolAttribute->id,
                            'value' => $value['value'],
                            'price' => $value['price'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    CommercialTooAttributeValue::insert($values);
                }

                // Load relationships
                $toolModel->load([
                    'commercialAttribute',
                    'commercialAttribute.commercialToolAttributeValues',
                    'user',
                    'type'
                ]);

                // Save to the array
                $toolModels[] = $toolModel;
            }
        });

        return $toolModels;  // return all created tools
    }

    //     public function updateCommercialTool($data, $toolId)
    //     {
    //     \DB::transaction(function () use ($data, $toolId) {
    //         // Find the existing CommercialTool record
    //         $toolModel = CommercialTool::findOrFail($toolId);

    //         // Update the main tool details
    //         $toolModel->update([
    //             'name_en' => $data['name_en'],
    //             'name_ar' => $data['name_ar'],
    //             'description_en' => $data['description_en'],
    //             'description_ar' => $data['description_ar'],
    //             'user_id' => $data['user_id'],
    //             'type_id' => $data['tool_type_id']
    //         ]);

    //         // Find or create the related attribute
    //         $toolAttribute = CommercialTooAttribute::updateOrCreate(
    //             ['tool_id' => $toolModel->id], // Condition to check for existing attribute
    //             [
    //                 'attribute_name_en' => $data['tool_attributes']['attribute_name_en'],
    //                 'attribute_name_ar' => $data['tool_attributes']['attribute_name_ar'],
    //             ]
    //         );

    //         // Update or insert attribute values
    //         foreach ($data['tool_attributes']['values'] as $value) {
    //             CommercialTooAttributeValue::updateOrCreate(
    //                 [
    //                     'tool_attribute_id' => $toolAttribute->id,
    //                     'value' => $value['value'],
    //                 ],
    //                 [
    //                     'price' => $value['price']
    //                 ]
    //             );
    //         }
    //     });

    //     // Reload relationships and return the updated model
    //     $toolModel = CommercialTool::with(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user', 'type'])
    //         ->findOrFail($toolId);

    //     return $toolModel;
    // }
    public function updateCommercialTool($data, $toolId)
    {
        DB::transaction(function () use ($data, $toolId) {
            // Find the existing CommercialTool record
            $toolModel = CommercialTool::findOrFail($toolId);

            // Update the main tool details
            $toolModel->update([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'description_en' => $data['description_en'],
                'description_ar' => $data['description_ar'],
                'user_id' => $data['user_id'],
                'type_id' => $data['tool_type_id'],
            ]);

            // Update or create the related attribute
            $toolAttribute = CommercialTooAttribute::updateOrCreate(
                ['tool_id' => $toolModel->id],  // Check for existing attribute
                [
                    'attribute_name_en' => $data['tool_attributes']['attribute_name_en'],
                    'attribute_name_ar' => $data['tool_attributes']['attribute_name_ar'],
                ]
            );

            // Sync attribute values
            $values = $data['tool_attributes']['values'];
            foreach ($values as $value) {
                CommercialTooAttributeValue::updateOrCreate(
                    [
                        'tool_attribute_id' => $toolAttribute->id,
                        'value' => $value['value'],
                    ],
                    [
                        'price' => $value['price'],
                    ]
                );
            }
        });

        // Reload relationships and return the updated model
        $toolModel = CommercialTool::with([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
        ])->findOrFail($toolId);

        return $toolModel;
    }

    public function addORdeletewishlist(array $data)
    {
        DB::beginTransaction();
        try {
            // Check if the user already exists in the waiting list
            $existingActivity = CommercialTool::where('id', $data['tool_id'])
                ->first();

            $existingActivityinwishlist = Wishlist::where('tool_id', $data['tool_id'])
                ->where('user_id', auth()->id())
                ->first();

            if ($existingActivity && !$existingActivityinwishlist) {
                // Add new record if the user doesn't exist
                $wishList = Wishlist::create([
                    'user_id' => auth()->id(),
                    'tool_id' => $data['tool_id'],
                    'type' => 'tool',
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Added to Wish list successfully.',
                    'message_ar' => 'تمت الإضافة إلى قائمة الرغبات بنجاح.',
                    'data' => $wishList,
                ], 201);
            } else {
                $wishListdeleted = Wishlist::where('tool_id', $data['tool_id'])->where('user_id', auth()->id())->delete();

                DB::commit();
                return response()->json([
                    'message' => 'Deleted from wish list successfully.',
                    'message_ar' => 'تم الحذف من قائمة الرغبات بنجاح.',
                    // 'data' => $wishListdeleted,
                ], 204);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_wishlist($perPage): LengthAwarePaginator
    {
        $userId = auth()->id();

        $toolModel = CommercialTool::with([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'toolImages',
            'user',
            'type',
        ])
            ->join('wishlists', 'wishlists.tool_id', '=', 'commercial_tools.id')
            ->where('wishlists.user_id', $userId)
            ->where('type', 'tool')
            ->orderBy('commercial_tools.id', 'desc')
            ->select('commercial_tools.*')  // avoid ambiguous column errors
            ->paginate($perPage);

        foreach ($toolModel as $tool) {
            $tool->sameUsersameProvider = ($tool->user_id == $userId) ? 'yes' : 'no';
            foreach ($tool->toolImages as $img) {
                $img->image_path = Str::startsWith($img->image_path, ['http://', 'https://'])
                    ? $img->image_path
                    : env('APP_URL') . 'storage/app/public/' . $img->image_path;
            }
        }

        return $toolModel;
    }
}
