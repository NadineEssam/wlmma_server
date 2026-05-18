<?php

namespace App\Services\Activity;

use App\Enums\AActivityStatusEnum;
use App\Http\Resources\ActivityResource;
use App\Models\ActivityImage;
use App\Models\Image;
use App\Repositories\Activity\ActivityRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ActivityService
{
    public function __construct(
        private ActivityRepository $repository
    ) {}

    public function getAllServiceProviderActivities_old($perPage, $serviceProviderId): LengthAwarePaginator
    {
        return $this->repository->allServiceProviderActivities($perPage, $serviceProviderId);
    }

    public function getAllActivitiesForAdmin($perPage): LengthAwarePaginator
    {
        return $this->repository->allActivitiesForAdmin($perPage);
    }

    public function getfeatured_trips($perPage): LengthAwarePaginator
    {
        return $this->repository->getfeatured_trips($perPage);
    }

    public function getAllServiceProviderActivities($perPage): LengthAwarePaginator
    {
        return $this->repository->allServiceProviderActivities($perPage);
    }

    public function get_booking($perPage, $type): LengthAwarePaginator
    {
        return $this->repository->get_booking($perPage, $type);
    }

    public function getFilteredActivities($filters, $perPage)
    {
        return $this->repository->filterActivities($filters, $perPage);
    }

    public function getFilteredActivities_type($filters, $perPage)
    {
        return $this->repository->filterActivities_type($filters, $perPage);
    }

    public function createActivity(array $data, $serviceProviderId, $images)
    {
        $data['user_id'] = $serviceProviderId;
        $data['status_id'] = AActivityStatusEnum::ACTIVE;
        $activity = $this->repository->create($data);
        $imagePaths = [];
        foreach ($images as $image) {
            $path = $image->store('activities', 'public');  // Store in 'public/activities' directory
            $imagePaths[] = [
                'image_path' => $path,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        Image::insert($imagePaths);

        $lastId = Image::orderByDesc('id')->first()?->id ?: 0;

        $ids = range($lastId - count($images) + 1, $lastId);
        // link images to activities
        $imageActivity = [];
        foreach ($ids as $id) {
            $imageActivity[] = [
                'image_id' => $id,
                'activity_id' => $activity->id,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        ActivityImage::insert($imageActivity);
        $activity->load('ActivityImages', 'activityPlans', 'tools');

        return new ActivityResource($activity);
    }

    public function updateActivity($activity, array $data)
    {
        return $this->repository->update($activity, $data);
    }

    public function deleteActivity($activity)
    {
        return $this->repository->delete($activity);
    }

    // public function findActivity($id)
    // {
    //     return $this->repository->find($id);
    // }

    public function addORdeletewishlist(array $data)
    {
        return $this->repository->addORdeletewishlist($data);
    }

    public function get_wishlist($perPage): LengthAwarePaginator
    {
        return $this->repository->get_wishlist($perPage);
    }

    /* ActivityTypes */

    public function getActivityTypes($perPage)
    {
        return $this->repository->activityTypes($perPage);
    }

    public function creatActivityTypes(array $data, $image)
    {
        return DB::transaction(function () use ($data, $image) {
            // Handle image compression before sending to repository
            if ($image) {
                $originalExtension = strtolower($image->getClientOriginalExtension());
                $filename = uniqid() . '.' . $originalExtension;
                $storageDir = storage_path('app/public/activityTypes/');
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

                // Store relative public path to DB
                $publicPath = 'storage/app/public/activityTypes/' . $filename;

                // Add image path to data before sending to repository
                $data['image'] = env('APP_URL') . $publicPath;
            }

            // Create the offer with image path (if exists)
            // dd($data);
            $activityType = $this->repository->createActivityType($data);

            return $activityType;
        });
    }

    public function findActivityType($id)
    {
        return $this->repository->findActivityType($id);
    }

    public function updateActivityType($activityType, array $data)
    {
        return $this->repository->updateActivityType($activityType, $data);
    }

    public function deleteActivityType($activityType)
    {
        // dd($this->repository->delete($offer));
        return $this->repository->deleteActivityType($activityType);
    }

    /* ActivityTypes */
}
