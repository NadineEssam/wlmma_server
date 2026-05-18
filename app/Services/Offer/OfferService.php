<?php

namespace App\Services\Offer;

use App\Models\Image;
use App\Models\Offer;
use App\Models\OfferImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OfferResource;
use App\Repositories\Offer\OfferRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class OfferService
{

    public function __construct(private OfferRepository $repository) {}

    public function all_offers($perPage): LengthAwarePaginator
    {
        return $this->repository->all_offers($perPage);
    }


    // public function createOffer(array $data, $image)
    // {
    //     return DB::transaction(function () use ($data, $image) {
    //         // Create the offer
    //         $offer = $this->repository->create($data);

    //         // Handle the single image
    //         if ($image) {
    //             // Store the image and get the path
    //             $path = env('APP_URL') . 'storage/app/public/' . $image->store('offers', 'public');

    //             // Update the offer with the image path
    //             $offer->update(['image' => $path]);
    //         }

    //         // Return the offer with its associated image
    //         return $offer;
    //     });
    // }

    public function createOffer(array $data, $image)
    {
    return DB::transaction(function () use ($data, $image) {
        // Handle image compression before sending to repository
        if ($image) {
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
                imagejpeg($imageResource, $storagePath, 75); // 75% quality
            } elseif ($originalExtension === 'png') {
                $imageResource = imagecreatefrompng($tempPath);
                imagepng($imageResource, $storagePath, 6); // compression level 6
            } else {
                throw new \Exception('Unsupported image type');
            }

            imagedestroy($imageResource);

            // Store relative public path to DB
            $publicPath = 'storage/app/public/offers/' . $filename;

            // Add image path to data before sending to repository
            $data['image'] = env('APP_URL') . '/' . $publicPath;
        }

        // Create the offer with image path (if exists)
        $offer = $this->repository->create($data);

        return $offer;
    });
}





    public function updateOffer($offer, array $data)
    {
        return $this->repository->update($offer, $data);
    }

    public function deleteOffer($offer)
    {
        // dd($this->repository->delete($offer));
        return $this->repository->delete($offer);
    }

    public function findOffer($id)
    {
        return $this->repository->find($id);
    }
}
