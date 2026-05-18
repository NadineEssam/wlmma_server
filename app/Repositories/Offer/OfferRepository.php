<?php

namespace App\Repositories\Offer;

use App\Models\Tool;
use App\Models\Offer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ActivityResource;
use Illuminate\Pagination\LengthAwarePaginator;

class OfferRepository
{
    public function all_offers($perPage): LengthAwarePaginator
    {
        // Eager load relationships to avoid N+1 queries
        $offers = Offer::select('*')
            ->orderBy('offers.id', 'desc')
            ->paginate($perPage);

        // Process each activity
        // $offers->each(function ($offer) {
        //     // Update image paths with the full URL
        //     $offer->offerImage->each(function ($image) {
        //         $image->image_path = asset('storage/app/public/' . $image->image_path);
        //     });
        // });

        return $offers;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Prepare data for the offer table
            $offerData = [
                'title_ar' => $data['title_ar'],
                'title_en' => $data['title_en'],
                'link' => $data['link'] ?? null,
                'image' => $data['image'] ?? null, // Use null coalescing operator
            ];

            // Uncomment this for debugging if needed
            // dd($offerData);

            // Insert offer and get the offer id
            $offer = Offer::create($offerData);
            return $offer;
        });
    }



    public function update($offer, array $data)
    {
        $offer->update($data);
        return $offer;
    }

    public function delete($offer)
    {
        return $offer->delete();
    }


    public function find($id)
    {
        // Fetch the activity with related data
        $offer = Offer::select('*')
            ->where('offers.id', $id)
            ->first();

        // Check if the activity exists
        if (!$offer) {
            return response()->json([
                'message' => 'Offer not found',
                'message_ar' => 'لم يتم العثور على العرض',
            ], 404);
        }

        // Attach images with full paths
        if ($offer->relationLoaded('OfferImage')) {
            $offer->offeractivityImage->transform(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path; // Corrected path
                return $image;
            });
        }

        return $offer;
    }
}
