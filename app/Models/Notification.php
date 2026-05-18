<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /*
     * Types
     */

    public const CHAT_MESSAGE_TYPE = 'chat_message';
    public const WELCOME_TYPE = 'welcome';
    public const GENERAL_TYPE = 'general';
    public const WALLET_TYPE = 'wallet';
    public const SUGGESTION_TYPE = 'suggestion';
    public const PROFILE_COMPLETED_TYPE = 'profile_completed';
    public const ACTIVITY_COMPLETED_TYPE = 'activity_completed';
    public const RESERVATION_BOOKED_TYPE = 'reservation_booked';
    public const RESERVATION_ACCEPTED_TYPE = 'reservation_accepted';
    public const RESERVATION_CLOSED_TYPE = 'reservation_closed';
    public const RESERVATION_CANCELLED_BY_USER_TYPE = 'reservation_cancelled_by_user';
    public const RESERVATION_CANCELLED_BY_PROVIDER_TYPE = 'reservation_cancelled_by_provider';
    public const RESERVATION_REJECTED_TYPE = 'reservation_rejected';
    public const RESERVATION_APPROVAL_TYPE = 'reservation_approval';
    public const NO_AVALIABLE_SEATS = 'no_avaliable_seats';
    public const PAYMENT_SUCCESS_TYPE = 'payment_success';
    public const PAYMENT_FAILED_TYPE = 'payment_failed';
    public const ACTVITY_ADD_TYPE = 'add_activity';
    public const COMMERCIAL_TOOL_ADD_TYPE = 'add_commercial_tool';

    public const NOTIFICATIONS_ALLOWED_TYPES = [
        self::CHAT_MESSAGE_TYPE,
        self::WELCOME_TYPE,
        self::GENERAL_TYPE,
        self::WALLET_TYPE,
        self::SUGGESTION_TYPE,
        self::PROFILE_COMPLETED_TYPE,
        self::ACTIVITY_COMPLETED_TYPE,
        self::RESERVATION_BOOKED_TYPE,
        self::RESERVATION_ACCEPTED_TYPE,
        self::RESERVATION_CLOSED_TYPE,
        self::RESERVATION_CANCELLED_BY_USER_TYPE,
        self::RESERVATION_CANCELLED_BY_PROVIDER_TYPE,
        self::RESERVATION_REJECTED_TYPE,
        self::RESERVATION_APPROVAL_TYPE,
        self::NO_AVALIABLE_SEATS,
        self::PAYMENT_SUCCESS_TYPE,
        self::PAYMENT_FAILED_TYPE,
        self::ACTVITY_ADD_TYPE,
        self::COMMERCIAL_TOOL_ADD_TYPE,
    ];

    /*
     * Actions
     */

    public static function add_new(string $add): string
    {
        return 'New_' . $add;
    }

    public static function toReservation(int $id): string
    {
        return 'reservation/' . $id;
    }

    /*
     * rest of class
     */

    protected $fillable = [
        'user_id',
        'user_acting_as',
        'title_en',
        'title_ar',
        'body_en',
        'body_ar',
        'type',
        'book_id',
        'order_id',
        'action',
        'is_seen',
    ];

    protected $casts = [
        'is_seen' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }
}
