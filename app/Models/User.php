<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'deactivate_request',
        'is_deactive',
        'apple_id',
        'google_id',
        'name',
        'last_name',
        'code',
        'email',
        'acting_as',
        'is_approved_provider',
        'gender',  // New
        'street',  // New
        'city',  // New
        'state',  // New
        'country',  // New
        'postcode',  // New
        'registration_id',
        'password',
        'country_code',
        'phone_number',
        'user_types_id',
        'iban',
        'iban_image',
        'trn',
        'trn_image',  // New
        'company_logo',  // New
        'cr',
        'cr_image',
        'live_photo',
        'national_id',
        'national_id_image',  // New
        'fcm_token',
        'tour_guide'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // protected static function booted()
    // {
    //     static::creating(function ($user) {
    //         $user->code = self::generateUniqueCode();
    //     });
    // }

    // private static function generateUniqueCode()
    // {
    //     do {
    //         $code = strtoupper(Str::random(8));
    //     } while (self::where('code', $code)->exists());

    //     return $code;
    // }

    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_types_id');  // 'user_types_id' is the foreign key in the users table
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function isServiceProvider()
    {
        return $this->role === 'service_provider';
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the FCM tokens associated with the user.
     */
    public function fcmTokens()
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function rate()
    {
        return $this->hasMany(Rating::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Payment
    public function billingInformation()
    {
        return $this->hasMany(BillingInformation::class);
    }

    // relationship
    public function userImage()
    {
        return $this->belongsTo(File::class, 'live_photo');
    }

    // accessoris
    public function getUserImageUrlAttribute()
    {
        if ($this->relationLoaded('userImage') && $this->userImage) {
            // فقط خذ اسم الصورة، لأن مسارها بالكامل تم تخزينه بشكل خاطئ
            $fileName = $this->userImage->name;
            return env('APP_URL') . 'storage/' . $fileName;
        }

        return null;
    }

    public function livePhotoFile()
    {
        return $this->belongsTo(File::class, 'live_photo');
    }

    public function companyLogoFile()
    {
        return $this->belongsTo(File::class, 'company_logo');
    }

    public function nationalIdFile()
    {
        return $this->belongsTo(File::class, 'national_id_image');
    }

    public function ibanImageFile()
    {
        return $this->belongsTo(File::class, 'iban_image');
    }

    public function trnImageFile()
    {
        return $this->belongsTo(File::class, 'trn_image');
    }

    public function taxImageFile()
    {
        return $this->belongsTo(File::class, 'tax_image');
    }

    public function crImageFile()
    {
        return $this->belongsTo(File::class, 'cr_image');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
