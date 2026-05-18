<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommercialToolResource;
use App\Http\Resources\CommercialToolResourceSupplies;
use App\Models\CommercialTool;
use App\Models\SuppliesRent;
use App\Services\CommercialTool\CommercialToolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserCommercialToolsController extends Controller
{
    public function __construct(
        private CommercialToolService $service
    ) {}

    // public function index(Request $request)
    // {
    //     $tools = CommercialTool::with(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user', 'type', 'toolImages'])->paginate($request->per_page?:15);

    //     return CommercialToolResource::collection($tools);
    // }

    // public function index(Request $request)
    // {
    //     $tools = CommercialTool::with([
    //         'commercialAttribute',
    //         'commercialAttribute.commercialToolAttributeValues',
    //         'user',
    //         'type',
    //         'toolImages'
    //     ]);

    //     if (auth()->check()) {
    //         $userId = auth()->id();

    //         // Add the 'is_favourite' column
    //         $tools->addSelect([
    //             'commercial_tools.*', // Ensure all tool columns are selected
    //             DB::raw('(CASE WHEN wishlists.tool_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
    //         ]);

    //         // Join the 'wishlists' table
    //         $tools->leftJoin('wishlists', function ($join) use ($userId) {
    //             $join->on('wishlists.tool_id', '=', 'commercial_tools.id')
    //                 ->where('wishlists.user_id', '=', $userId);
    //         });
    //     }

    //     // Paginate results
    //     $tools = $tools->paginate($request->per_page ?: 15);

    //     // return CommercialToolResource::collection($tools);
    //     return $tools;
    // }

    // public function index(Request $request)
    // {
    //     $tools = CommercialTool::with([
    //         'commercialAttribute',
    //         'commercialAttribute.commercialToolAttributeValues',
    //         'user',
    //         'type',
    //         'toolImages'
    //     ]);

    //     if (auth()->check()) {
    //         $userId = auth()->id();

    //         // Add the 'is_favourite' column
    //         $tools->addSelect([
    //             'commercial_tools.*', // Ensure all tool columns are selected
    //             DB::raw('(CASE WHEN wishlists.tool_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
    //         ]);

    //         // Join the 'wishlists' table
    //         $tools->leftJoin('wishlists', function ($join) use ($userId) {
    //             $join->on('wishlists.tool_id', '=', 'commercial_tools.id')
    //                 ->where('wishlists.user_id', '=', $userId);
    //         });
    //     }

    //     // Paginate results
    //     $tools = $tools->paginate($request->per_page ?: 15);

    // // **Transform image paths before returning the response**
    //     $tools->getCollection()->transform(function ($tool) {
    //         $tool->tool_images = $tool->toolImages->map(function ($image) {
    //             return [
    //                 'id' => $image->id,
    //                 'image_path' => url('storage/app/public/' . $image->image_path), // Correct full path
    //             ];
    //         });
    //         return $tool;
    //     });

    //         dd($tools);

    //     return $tools;
    // }

    public function index(Request $request)
    {
        $tools = CommercialTool::with([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
            'toolImages'
        ]);

        if (auth()->check()) {
            $userId = auth()->id();

            // Add the 'is_favourite' column
            $tools->addSelect([
                'commercial_tools.*',  // Ensure all tool columns are selected
                DB::raw('(CASE WHEN wishlists.tool_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
            ]);

            // Join the 'wishlists' table
            $tools->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('wishlists.tool_id', '=', 'commercial_tools.id')
                    ->where('wishlists.user_id', '=', $userId);
            });
        }

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

    // public function forSale(Request $request)
    // {
    //     $tools = CommercialTool::with(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user', 'type', 'toolImages'])
    //         ->where('type_id', 2)->paginate($request->per_page ?: 15);

    //     return CommercialToolResource::collection($tools);
    // }

    public function forSale(Request $request)
    {
        $userId = auth()->id();
        // dd(auth()->user());
        $tools = CommercialTool::with([
            'commercialAttribute',
            'commercialAttribute.commercialToolAttributeValues',
            'user',
            'type',
            'toolImages'
        ])
            ->select([
                'commercial_tools.*',  // Ensure all tool columns are selected
                DB::raw('(CASE WHEN wishlists.tool_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
            ])
            ->where('type_id', 2)
            ->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('wishlists.tool_id', '=', 'commercial_tools.id')
                    ->where('wishlists.user_id', '=', $userId);
            })
            ->orderBy('commercial_tools.id', 'desc')
            ->paginate($request->per_page ?: 15);
        // Add the custom field for each item in the collection
        $tools->getCollection()->transform(function ($tool) use ($userId) {
            $tool->sameUsersameProvider = ($tool->user_id == $userId) ? 'yes' : 'no';
            return $tool;
        });
        return CommercialToolResource::collection($tools);
    }

    public function forRent(Request $request)
    {
        $userId = auth()->id();

        $tools = SuppliesRent::with([
            'tool.commercialAttribute',
            'tool.commercialAttribute.commercialToolAttributeValues',
            'tool.type',
            'supplier',
            'renter',
            // شلنا tool.toolImages من هنا لأننا هنتعامل معاها في الـ Resource
        ])
            ->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('supplies_rents.tool_id', '=', 'wishlists.tool_id')
                    ->where('wishlists.user_id', '=', $userId);
            })
            ->whereHas('tool', function ($q) {
                $q->where('type_id', '<>', 2);
            })
            ->select('supplies_rents.*')
            ->selectRaw('CASE WHEN wishlists.id IS NOT NULL THEN true ELSE false END as is_favourite')
            ->orderBy('supplies_rents.tool_id', 'desc')
            ->paginate($request->per_page ?: 15);

        $tools->getCollection()->transform(function ($item) use ($userId) {
            $item->sameUsersameProvider = ($item->supplier_id == $userId) ? 'yes' : 'no';
            return $item;
        });

        return CommercialToolResourceSupplies::collection($tools);
    }

    // public function show(CommercialTool $tool)
    // {
    // $tool->load(['commercialAttribute', 'commercialAttribute.commercialToolAttributeValues', 'user', 'type', 'toolImages']);

    // return new CommercialToolResource($tool);

    // }
    public function show($id)
    {
        $tool = CommercialTool::select([
            'commercial_tools.*',
            DB::raw('(CASE WHEN wishlists.tool_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
        ])
            ->leftJoin('wishlists', function ($join) {
                $join
                    ->on('wishlists.tool_id', '=', 'commercial_tools.id')
                    ->where('wishlists.user_id', auth()->id());
            })
            ->with([
                'commercialAttribute',
                'commercialAttribute.commercialToolAttributeValues',
                'user',
                'type',
                'toolImages'
            ])
            ->findOrFail($id);
        $tool->sameUsersameProvider = ($tool->user_id == auth()->id()) ? 'yes' : 'no';
        return new CommercialToolResource($tool);
    }

    public function addORdeletewishlist(Request $request)
    {
        $commercial_tools = $this->service->addORdeletewishlist($request->all(), $request->per_page);

        return response()->json($commercial_tools);
    }

    public function wishlist(Request $request)
    {
        $commercial_tools = $this->service->get_wishlist($request->per_page);

        return response()->json($commercial_tools);
    }
}
