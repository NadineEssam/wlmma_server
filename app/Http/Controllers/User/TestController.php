<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\PushNotificationRequest;
use App\Mail\BookingNotificationMail;
use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\CommercialTool;
use App\Models\Offer;
use App\Models\Tool;
use App\Models\ToolAttribute;
use App\Models\ToolAttributeValues;
use App\Models\User;
use App\Models\WaitingList;
use App\Services\Activity\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPMailer\PHPMailer\PHPMailer;

require base_path('vendor/phpmailer/phpmailer/src/PHPMailer.php');
require base_path('vendor/phpmailer/phpmailer/src/SMTP.php');
require base_path('vendor/phpmailer/phpmailer/src/Exception.php');

class TestController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();  // or get it from request if passed differently

        $offers = Offer::select('id', 'link', 'image')->latest()->take(4)->get();

        // $tourGuides = User::with('userType')
        //     ->where('tour_guide', 1)
        //     ->latest()
        //     ->take(4)
        //     ->get();

        $adventures = ActivityType::select('id', 'name_ar', 'name_en', 'image')->latest()->take(4)->get();

        // $from = Carbon::today()->format('Y-m-d');
        // $to = Carbon::now()->addMonths(3)->format('Y-m-d');

        $from = Carbon::today();  // Carbon object
        $to = Carbon::now()->addMonths(3);  // Carbon object

        // // dd('From => ' . $from . ' To => ' . $to);
        // $singleDayTrips = Activity::with(['activityImages', 'activityType'])
        //     ->select(
        //         'activities.id',
        //         'activities.activity_type_id',
        //         'activities.title_en',
        //         'activities.title_ar',
        //         'activities.city_name_en',
        //         'activities.city_name_ar',
        //         'activities.country_name_en',
        //         'activities.country_name_ar',
        //         'activities.at_home',
        //         'activities.price',
        //         'activities.start_date',
        //         'activities.activity_single_dates',
        //         'activities.user_id',
        //         DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating'),
        //         DB::raw('CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END as is_favourite')
        //     )
        //     ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
        //     ->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id')
        //     ->leftJoin('wishlists', function ($join) use ($userId) {
        //         $join
        //             ->on('wishlists.activity_id', '=', 'activities.id')
        //             ->where('wishlists.user_id', '=', $userId);
        //     })
        //     ->where('plan_activity', 'no')
        //     // ->whereBetween('start_date', [$from, $to])
        //     ->whereBetween('activity_single_dates', [$from, $to])
        //     ->groupBy(
        //         'activities.id',
        //         'activities.user_id',
        //         'activities.activity_type_id',
        //         'activities.title_en',
        //         'activities.title_ar',
        //         'activities.city_name_en',
        //         'activities.city_name_ar',
        //         'activities.country_name_en',
        //         'activities.country_name_ar',
        //         'activities.at_home',
        //         'activities.price',
        //         'activities.start_date',
        //         'activities.activity_single_dates',
        //         'wishlists.activity_id',
        //         'activity_types.id'  // Add this line to avoid SQL error
        //     )
        //     ->orderBy('activities.created_at', 'desc')
        //     ->take(4)
        //     ->get();
        $singleDayTrips = Activity::with(['activityImages', 'activityType'])
            ->select(
                'activities.id',
                'activities.activity_type_id',
                'activities.title_en',
                'activities.title_ar',
                'activities.city_name_en',
                'activities.city_name_ar',
                'activities.country_name_en',
                'activities.country_name_ar',
                'activities.at_home',
                'activities.price',
                'activities.start_date',
                'activities.activity_single_dates',
                'activities.user_id',
                DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating'),
                DB::raw('CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END as is_favourite')
            )
            ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
            ->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id')
            ->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('wishlists.activity_id', '=', 'activities.id')
                    ->where('wishlists.user_id', $userId);
            })
            ->where('plan_activity', 'no')
            ->groupBy(
                'activities.id',
                'activities.user_id',
                'activities.activity_type_id',
                'activities.title_en',
                'activities.title_ar',
                'activities.city_name_en',
                'activities.city_name_ar',
                'activities.country_name_en',
                'activities.country_name_ar',
                'activities.at_home',
                'activities.price',
                'activities.start_date',
                'activities.activity_single_dates',
                'wishlists.activity_id',
                'activity_types.id'
            )
            ->orderBy('activities.created_at', 'desc')
            ->get();

        $singleDayTrips = $singleDayTrips->filter(function ($activity) use ($from, $to) {
            $dates = $activity->activity_single_dates
                ? array_map('trim', explode(',', $activity->activity_single_dates))
                : [];

            // Log::info(['dates' => $dates]);

            foreach ($dates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);

                    if ($date->between($from, $to)) {
                        return true;  // أول تاريخ صالح يكفي
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return false;
        })->take(4)->values();

        foreach ($singleDayTrips as $trip) {
            // echo '$userId => '.$userId.'$trip->user_id => '.$trip->user_id;
            // Compare current trip's user_id with the logged-in user
            $trip->sameUsersameProvider = ($trip->user_id == $userId) ? 'yes' : 'no';
            foreach ($trip->activityImages as $image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Laravel's storage path
            }
        }

        $planTrips = Activity::with(['activityImages', 'activityType'])
            ->select(
                'activities.id',
                'activities.activity_type_id',
                'activities.title_en',
                'activities.title_ar',
                'activities.city_name_en',
                'activities.city_name_ar',
                'activities.country_name_en',
                'activities.country_name_ar',
                'activities.at_home',
                'activities.price',
                'activities.start_date',
                // 'activities.user_id',
                DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating'),
                DB::raw('CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END as is_favourite')
            )
            ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
            ->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id')
            ->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('wishlists.activity_id', '=', 'activities.id')
                    ->where('wishlists.user_id', '=', $userId);
            })
            ->where('plan_activity', 'yes')
            ->whereBetween('start_date', [$from, $to])
            ->groupBy(
                'activities.id',
                'activities.activity_type_id',
                'activities.title_en',
                'activities.title_ar',
                'activities.city_name_en',
                'activities.city_name_ar',
                'activities.country_name_en',
                'activities.country_name_ar',
                'activities.at_home',
                'activities.price',
                'activities.start_date',
                'wishlists.activity_id',
                'activity_types.id'  // Add this line to avoid SQL error
            )
            ->orderBy('activities.created_at', 'desc')
            ->take(4)
            ->get();

        foreach ($planTrips as $plantrip) {
            $plantrip->sameUsersameProvider = ($plantrip->user_id == $userId) ? 'yes' : 'no';
            foreach ($plantrip->activityImages as $image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Laravel's storage path
            }
        }

        // $featuredTrips = Activity::with(['activityImages', 'activityType'])
        //     ->select(
        //         'activities.id',
        //         'activities.activity_type_id',
        //         'activities.title_en',
        //         'activities.title_ar',
        //         'activities.city_name_en',
        //         'activities.city_name_ar',
        //         'activities.country_name_en',
        //         'activities.country_name_ar',
        //         'activities.at_home',
        //         'activities.price',
        //         'activities.start_date',
        //         'activities.user_id',
        //         DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating'),
        //         DB::raw('CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END as is_favourite')
        //     )
        //     ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
        //     ->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id')
        //     ->leftJoin('wishlists', function ($join) use ($userId) {
        //         $join
        //             ->on('wishlists.activity_id', '=', 'activities.id')
        //             ->where('wishlists.user_id', '=', $userId);
        //     })
        //     ->where('at_home', 'yes')
        //     // ->whereBetween('start_date', [$from, $to])

        //     ->groupBy(
        //         'activities.id',
        //         'activities.user_id',
        //         'activities.activity_type_id',
        //         'activities.title_en',
        //         'activities.title_ar',
        //         'activities.city_name_en',
        //         'activities.city_name_ar',
        //         'activities.country_name_en',
        //         'activities.country_name_ar',
        //         'activities.at_home',
        //         'activities.price',
        //         'activities.start_date',
        //         'wishlists.activity_id',
        //         'activity_types.id'  // Add this line to avoid SQL error
        //     )
        //     // ->orderBy('activities.created_at', 'desc')
        //     ->inRandomOrder()  // ✅ Randomize results
        //     ->take(4)
        //     ->get();

        // أولاً: تحقق من تنسيق البيانات في قاعدة البيانات
        // قد تكون البيانات مخزنة كسلسلة نصية وليس JSON

        $featuredTrips = Activity::with(['activityImages', 'activityType'])
            ->select(
                'activities.*',
                DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating'),
                DB::raw('CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END as is_favourite')
            )
            ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
            ->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id')
            ->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('wishlists.activity_id', '=', 'activities.id')
                    ->where('wishlists.user_id', $userId);
            })
            ->where('activities.at_home', 'yes')
            ->where(function ($query) use ($from, $to) {
                $query
                    ->where(function ($q) use ($from, $to) {
                        // للـ planned activities
                        $q
                            ->where('activities.plan_activity', 'yes')
                            ->whereNotNull('activities.start_date')
                            ->whereBetween('activities.start_date', [$from, $to]);
                    })
                    ->orWhere(function ($q) use ($from, $to) {
                        // للـ single activities - فلترة بعد جلب البيانات
                        $q
                            ->where('activities.plan_activity', 'no')
                            ->whereNotNull('activities.activity_single_dates');
                    });
            })
            ->groupBy('activities.id')
            ->inRandomOrder()
            ->get()
            ->filter(function ($activity) use ($from, $to) {
                // إذا كان planned activity
                if ($activity->plan_activity === 'yes' && $activity->start_date) {
                    return Carbon::parse($activity->start_date)->between($from, $to);
                }

                // إذا كان single activity
                if ($activity->plan_activity === 'no' && $activity->activity_single_dates) {
                    $dates = $this->parseActivityDates($activity->activity_single_dates);

                    foreach ($dates as $dateString) {
                        try {
                            if (Carbon::parse($dateString)->between($from, $to)) {
                                return true;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                return false;
            })
            ->take(4)
            ->values();

        foreach ($featuredTrips as $featuredTrip) {
            $featuredTrip->sameUsersameProvider = ($featuredTrip->user_id == $userId) ? 'yes' : 'no';
            foreach ($featuredTrip->activityImages as $image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Laravel's storage path
            }
        }

        $commercial_tools = CommercialTool::with([
            'type',
            'toolImages'
        ])
            ->select(
                'commercial_tools.id',
                'commercial_tools.name_en',
                'commercial_tools.name_ar',
                'commercial_tools.type_id',
                'commercial_tools.price',
                'commercial_tools.user_id',
                DB::raw('CASE WHEN wishlists.tool_id IS NOT NULL THEN 1 ELSE 0 END as is_favourite')
            )
            ->leftJoin('wishlists', function ($join) use ($userId) {
                $join
                    ->on('wishlists.tool_id', '=', 'commercial_tools.id')
                    ->where('wishlists.user_id', '=', $userId);
            })
            ->groupBy(
                'commercial_tools.id',
                'commercial_tools.name_en',
                'commercial_tools.name_ar',
                'commercial_tools.type_id',
                'commercial_tools.price',
                'commercial_tools.user_id',
                'wishlists.tool_id'
            )
            ->orderBy('commercial_tools.created_at', 'desc')
            ->where('type_id', 2)
            ->take(4)
            ->get();

        foreach ($commercial_tools as $tool) {
            $tool->sameUsersameProvider = ($tool->user_id == $userId) ? 'yes' : 'no';
            foreach ($tool->toolImages as $image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
            }
        }

        $flagBOOK = Booking::where('user_id', $userId)->get();
        foreach ($flagBOOK as $ff) {
            $aa = $ff->id;
            $aa2 = $ff->status_id;
            $zzz = false;
            if ($ff->status_id == 3) {
                $zzz = $ff->status_id == 3;
                break;
            }
        }

        return response()->json([
            'flagBOOK' => $zzz ?? null,
            'offers' => $offers,
            // 'tour_guides' => $tourGuides,
            'adventures' => $adventures,
            'single_day_trips' => $singleDayTrips,
            'plan_trips' => $planTrips,
            'featured_trips' => $featuredTrips,
            'commercial_tools' => $commercial_tools,
        ]);
    }

    public function testEmail(Request $request)
    {
        $tomemail = $request->email;
        // $tomemail = "sarahashaher0@gmail.com";
        try {
            $mail = new PHPMailer(true);
            // إعدادات السيرفر
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');  // أو 'tls' لو ssl معملش
            $mail->Port = env('MAIL_PORT');

            // المرسل والمستقبل
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($tomemail);

            // المحتوى
            $mail->isHTML(true);
            $mail->Subject = 'OTP';
            $mail->Body = 'Your OTP code is: DDDDD';
            // dd($mail);
            $mail->send();

            echo ('ENV MAIL_HOST => ' . env('MAIL_HOST') . "\n");
            echo ('ENV MAIL_USERNAME => ' . env('MAIL_USERNAME') . "\n");
            echo ('ENV MAIL_PASSWORD => ' . env('MAIL_PASSWORD') . "\n");
            echo ('ENV MAIL_ENCRYPTION => ' . env('MAIL_ENCRYPTION') . "\n");
            echo ('ENV MAIL_PORT => ' . env('MAIL_PORT') . "\n");
            echo ('ENV MAIL_FROM_ADDRESS => ' . env('MAIL_FROM_ADDRESS') . "\n");
            echo ('ENV MAIL_FROM_NAME => ' . env('MAIL_FROM_NAME') . "\n");
            echo ('Email sent without exceptions');

            return 'Email sent! Check logs for details.';
        } catch (\Throwable $e) {
            Log::error('Email failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return 'Error: ' . $e->getMessage();
        }
    }

    public function testEmailMailtip(Request $request)
    {
        $tomemail = $request->email;
        try {
            sendViewEmail($tomemail, __('Trip Has Been Booked'), 'emails.booking-notification', [
                'customerName' => 'Valued Customer',
                'activityTitle' => 'TESTTTTTT',
                'bookingStatus' => 'paid',
                'notificationType' => 'payment_completed',
            ]);

            return 'Email sent! Check logs for details.';
        } catch (\Throwable $e) {
            // dd($e);
            Log::error('Email failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return 'Error: ' . $e->getMessage();
        }
    }

    private function parseActivityDates($dates)
    {
        if (empty($dates)) {
            return [];
        }

        // حاول تحليل كـ JSON أولاً
        if ($this->isJson($dates)) {
            $decoded = json_decode($dates, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return is_array($decoded) ? $decoded : [];
            }
        }

        // حاول كـ CSV (فاصلة)
        $datesArray = array_map('trim', explode(',', $dates));

        // حاول إزالة الأقواس إذا كانت موجودة
        $cleanedDates = array_map(function ($date) {
            $date = trim($date);
            // إزالة الأقواس والأسعار
            $date = preg_replace('/^\[|\]$|^"|"$|\'/', '', $date);
            return trim($date);
        }, $datesArray);

        return array_filter($cleanedDates);
    }

    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
