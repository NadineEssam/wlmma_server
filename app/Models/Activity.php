<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'at_home',
        'user_id',
        'user_type',
        'spoken_lang',
        'title_ar',
        'description_ar',
        'city_name_ar',
        'city_name_en',
        'country_name_ar',
        'country_name_en',
        'time',
        'duration',
        'plan_activity',
        // 'seats_no',
        'price',
        'type_decducted_amount',
        'decducted_amount',
        'capacity',
        'status_id',
        'lat',
        'long',
        'is_tourguideable',
        'tourguide_price',
        'description_en',
        'title_en',
        'activity_type_id',
        'is_photographer_available',
        'photographer_price',
        'start_date',
        'activity_days',
        'activity_single_dates',
        'activity_times_start',
        'activity_times_end',
        'privacy_policy_en',
        'privacy_policy_ar',
        'cancel_policy_en',
        'cancel_policy_ar',
    ];

    protected $casts = [
        'time' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function serviceprovider()
    {
        // return $this->belongsTo(User::class);
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ActivityImages()
    {
        return $this->belongsToMany(Image::class, 'activity_images', 'activity_id', 'image_id');
    }

    // public function getImagesAttribute()
    // {
    //     $images = $this->ActivityImages;

    //     foreach ($images as &$image) {
    //         $image->image_path = env('APP_URL') . '/storage/' . $image->image_path;
    //     }
    //     return $images;
    // }
    public function getImagePathAttribute($value)
    {
        // Manually prepend the URL with the desired structure
        return env('APP_URL') . '/storage/app/public/' . $value;
    }

    public function activityPlans()
    {
        return $this->hasMany(ActivityPlan::class);
    }

    public function tools()
    {
        return $this->hasMany(Tool::class);
    }

    public function activityTools()
    {
        return $this->hasMany(ActivityTool::class);
    }

    // In Activity.php (Activity model)
    public function bookings()
    {
        // return $this->hasMany(Booking::class);
        return $this->hasMany(Booking::class, 'activity_id');
    }

    public function capacities()
    {
        return $this->hasMany(ActivityCapacity::class, 'activity_id');
    }

    // Optional: flat integer for API

    public function getCapacityValueAttribute()
    {
        return (int) optional($this->capacity->first())->capacity;
    }

    public function rate()
    {
        return $this->hasMany(Rating::class);
    }

    public function ActivityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');  // or just ActivityType::class if Laravel can infer the foreign key
    }
}
