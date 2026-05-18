<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\Offer\OfferRequest;
use App\Http\Requests\Offer\StoreOfferRequest;
use App\Http\Requests\Offer\UpdateOfferRequest;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Image;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\OfferImage;
use App\Models\Tool;
use App\Models\User;
use App\Services\Notification\NotificationManager;
use App\Services\Offer\OfferService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class OfferController extends Controller
{
    public function __construct(
        private OfferService $service
    ) {}

    // Show activity for specific service provider
    public function index(Request $request)
    {
        $offers = $this->service->all_offers($request->per_page);

        return response()->json($offers);
    }

    // public function store(StoreOfferRequest $request)
    // {
    //     $offer = $this->service->createOffer($request->only([
    //         'title_ar',
    //         'title_en',
    //     ]), $request->image);

    //     return response()->json($offer);
    // }

    // Store new Offer
    // public function store(StoreOfferRequest $request)
    // {
    //     $offer = $this->service->createOffer([
    //         'title_ar' => $request->input('title_ar'),
    //         'title_en' => $request->input('title_en'),
    //     ], $request->file('image')); // For a single image

    //     return response()->json($offer);
    // }
    public function store(StoreOfferRequest $request)
    {
        try {
            DB::beginTransaction();

            // Pass the validated data and image file to the service
            $offer = $this->service->createOffer($request->validated(), $request->file('image'));

            DB::commit();
            return response()->json($offer, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            info($e);
            return response()->json([], 500);
        }
    }

    // Show Specific Offer data
    public function show(Offer $offer)
    {
        $offer = $this->service->findOffer($offer->id);

        return response()->json($offer);
    }

    // Update Offer Data
    // public function update(UpdateOfferRequest $request, Offer $offer)
    // {
    //     $offer = $this->service->updateOffer($offer, $request->only([
    //         'title_en',
    //         'title_ar',
    //         'link',
    //     ]));
    //     return response()->json([
    //         'message' => 'Offer Updated successfully',
    //         'message_ar' => 'تم تحديث العرض بنجاح',
    //         'data' => $offer
    //     ], 200);
    //     // return response()->json($offer);
    // }

    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        // Handle image upload if exists
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // delete old image if exists
            if ($offer->image) {
                $oldImagePath = storage_path('app/public/offers/' . $offer->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // store new image
            // $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            // $storageDir = storage_path('app/public/offers/');
            // $image->move($storageDir, $filename);
            // $offer->image = $filename;

            $originalExtension = strtolower($image->getClientOriginalExtension());
            $filename = uniqid() . '.' . $originalExtension;
            $storageDir = storage_path('app/public/offers/');
            $storagePath = $storageDir . $filename;

            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $tempPath = $image->getRealPath();

            if (in_array($originalExtension, ['jpg', 'jpeg'])) {
                $imageResource = imagecreatefromjpeg($tempPath);
                imagejpeg($imageResource, $storagePath, 75);  // 75% quality
            } elseif ($originalExtension === 'png') {
                $imageResource = imagecreatefrompng($tempPath);
                imagepng($imageResource, $storagePath, 6);  // compression level 6
            } else {
                throw new \Exception('Unsupported image type');
            }

            imagedestroy($imageResource);

            $publicPath = 'storage/app/public/offers/' . $filename;

            $offer->image = env('APP_URL') . '/' . $publicPath;
        }

        // update other fields
        $offer = $this->service->updateOffer($offer, $request->only([
            'title_en',
            'title_ar',
            'link',
        ]));

        // save image if changed
        $offer->save();

        return response()->json([
            'message' => 'Offer Updated successfully',
            'message_ar' => 'تم تحديث العرض بنجاح',
            'data' => $offer
        ], 200);
    }

    // Delete Offer
    public function destroy($id)
    {
        $offer_data = Offer::findOrFail($id);
        $offer = $this->service->deleteOffer($offer_data);
        return response()->json([
            'message' => 'Offer Deleted successfully',
            'message_ar' => 'تم حذف العرض بنجاح',
        ], 200);
    }
}
