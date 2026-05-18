<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AActivityStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityImage;
use App\Models\ActivityPlan;
use App\Models\Image;
use App\Models\Tool;
use App\Models\ToolAttribute;
use App\Models\ToolAttributeValues;
use App\Models\User;
use App\Services\Activity\ActivityService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AdminUserController extends Controller
{
    public function __construct(
        private ActivityService $service
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        // Fetch activities along with the related activity images and selected fields
        // $users = User::join('activities', 'activities.user_id', '=', 'users.id')->paginate($perPage);
        // $users = User::with('activities')
        //     ->select('*')
        //     ->paginate($perPage);
        $users = User::with(['userType'])
            ->select('*')
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?: 15);
            
            $users->getCollection()->transform(function ($user) {
            // live_photo
            if ($user->live_photo) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->live_photo)->name;
                // Retrieve the user's profile image if available
                $profileImage = null;
                // Assuming the image is stored in 'storage/activities'
                $user->live_photo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->live_photo = null;
            }

            // National Id Image
            if ($user->national_id_image) {
                // $path = DB::table('files')->find($user->national_id_image)->path;
                $national_id_image_name = DB::table('files')->find($user->national_id_image)->name;
                // Retrieve the user's profile image if available
                $national_id_image = null;
                // Assuming the image is stored in 'storage'
                $user->national_id_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
            } else {
                $user->national_id_image = null;
            }

            // iban_image
            if ($user->iban_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->iban_image)->name;
                // Retrieve the user's profile image if available
                $iban_image = null;
                // Assuming the image is stored in 'storage'
                $user->iban_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->iban_image = null;
            }

            // cr_image
            if ($user->cr_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->cr_image)->name;
                // Retrieve the user's profile image if available
                $cr_image = null;
                // Assuming the image is stored in 'storage'
                $user->cr_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->cr_image = null;
            }

            // trn_image
            if ($user->trn_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->trn_image)->name;
                // Retrieve the user's profile image if available
                $trn_image = null;
                // Assuming the image is stored in 'storage'
                $user->trn_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->trn_image = null;
            }

            // company_logo
            if ($user->company_logo) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name2 = DB::table('files')->find($user->company_logo)->name;
                // Retrieve the user's company_logo if available
                $company_logo = null;
                // Assuming the image is stored in 'storage/'
                $user->company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
            } else {
                $user->company_logo = null;
            }
            return $user;
        });
            
        return $users;
    }

    public function type_filter(Request $request): LengthAwarePaginator
    {
        // if ($request->user_types_id) {
            $users = User::with(['userType'])->where('user_types_id', $request->user_types_id)/*->where('is_approved_provider','yes')*/->orderBy('id', 'desc')->paginate($request->per_page ?: 15);
        // } else {
        //     $users = User::with(['userType'])->where('is_approved_provider','yes')->orderBy('id', 'desc')->paginate($request->per_page ?: 15);
        // }
        $users->getCollection()->transform(function ($user) {
            // live_photo
            if ($user->live_photo) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->live_photo)->name;
                // Retrieve the user's profile image if available
                $profileImage = null;
                // Assuming the image is stored in 'storage/activities'
                $user->live_photo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->live_photo = null;
            }

            // National Id Image
            if ($user->national_id_image) {
                // $path = DB::table('files')->find($user->national_id_image)->path;
                $national_id_image_name = DB::table('files')->find($user->national_id_image)->name;
                // Retrieve the user's profile image if available
                $national_id_image = null;
                // Assuming the image is stored in 'storage'
                $user->national_id_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
            } else {
                $user->national_id_image = null;
            }

            // iban_image
            if ($user->iban_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->iban_image)->name;
                // Retrieve the user's profile image if available
                $iban_image = null;
                // Assuming the image is stored in 'storage'
                $user->iban_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->iban_image = null;
            }

            // cr_image
            if ($user->cr_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->cr_image)->name;
                // Retrieve the user's profile image if available
                $cr_image = null;
                // Assuming the image is stored in 'storage'
                $user->cr_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->cr_image = null;
            }

            // trn_image
            if ($user->trn_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($user->trn_image)->name;
                // Retrieve the user's profile image if available
                $trn_image = null;
                // Assuming the image is stored in 'storage'
                $user->trn_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $user->trn_image = null;
            }

            // company_logo
            if ($user->company_logo) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name2 = DB::table('files')->find($user->company_logo)->name;
                // Retrieve the user's company_logo if available
                $company_logo = null;
                // Assuming the image is stored in 'storage/'
                $user->company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
            } else {
                $user->company_logo = null;
            }
            return $user;
        });
        return $users;
        // return response()->json([
        //     'message' => 'Success',
        //     "data" => $users
        // ]);
    }
}
