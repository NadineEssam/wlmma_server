<?php

namespace App\Http\Controllers\Admin;

use App\Core\Helpers\ResponseHelper;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleAdminRequest;
use App\Http\Requests\Vendor\CreateVendorRequest;
use App\Http\Resources\Admin\AdminPermissionResource;
use App\Http\Resources\Admin\AdminResource;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Providesrappoverequest;
use App\Models\User;
use App\Services\Admin\AdminService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(
        protected AdminService $adminService
    ) {}

    public function me(Request $request)
    {
        $admin = auth('admins')->user();

        if (!$admin) {
            return response()->json([
                'error' => 'Unauthenticated',
                'error_ar' => 'غير مصادق عليه',
            ], 401);
        }

        $data = new AdminResource($request->user());

        return response()->json([
            'data' => $data,
        ]);
    }

    public function listAdminPermissions(Request $request)
    {
        $admin = auth('admins')->user();

        if (!$admin) {
            return response()->json([
                'error' => 'Unauthenticated',
                'error_ar' => 'غير مصادق عليه',
            ], 401);
        }

        $data = AdminPermissionResource::collection($this->adminService->listAdminPermissions($request));

        return response()->json([
            'data' => $data,
            'meta' => ['total' => count($data)],
        ]);
    }

    public function assignRoleToAdmin(RoleAdminRequest $request)
    {
        $this->adminService->assignRoleToAdmin($request);

        return response()->json([
            // 'message' => _('SUCCESS')
            'message' => 'success',
            'message_ar' => 'نجاح',
        ], 200);
        // ], 204);
    }

    public function revokeRoleFromAdmin(RoleAdminRequest $request)
    {
        $status = $this->adminService->revokeRoleFromAdmin($request);

        return response()->json([
            'message' => $status == 1 ? 'success' : 'failure',
            'message_ar' => $status == 1 ? 'نجاح' : 'فشل',
        ]);
    }

    // update the fcm_token column in admins table
    public function saveFcmToken(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        // Get the authenticated user
        $admin = auth()->user();
        // Update the fcm_token for the authenticated user
        $admin->update([
            'fcm_token' => $request->fcm_token,
        ]);

        // Return a success message
        return response()->json([
            'message' => 'Token saved successfully.',
            'message_ar' => 'تم حفظ الرمز المميز بنجاح.',
        ], 200);
    }

    public function validateSaudiPhoneNumber($phone)
    {
        // Normalize the phone number by removing spaces or unwanted characters
        $phone = trim($phone);

        // Check if the phone number starts with '+9665' and is 12 characters long
        if (substr($phone, 0, 5) === '+9665' && strlen($phone) === 13) {
            // Ensure all remaining characters are digits
            $remainingDigits = substr($phone, 5);  // Get everything after '+9665'
            if (ctype_digit($remainingDigits)) {
                return true;  // Valid phone number
            }
        }

        return false;  // Invalid phone number
    }

    public function acceptProvider(Request $request)
    {
        $request->validate([
            // 'phone_number' => 'required|exists:users,phone_number',
            'user_id' => 'required|exists:users,id',
            'approve' => 'required|in:yes,no',
        ]);
        // $phone = $request->phone_number;
        $user_id = $request->user_id;
        $approve = $request->approve;
        // if (!$this->validateSaudiPhoneNumber($phone)) {
        //     return response()->json([
        //         'message' => 'The phone number is not valid.',
        //         'message_ar' => 'رقم الهاتف غير صالح.',
        //     ], 422);
        // }
        $user = User::with('userType')->where('id', $user_id)->first();
        // if ($user->user_types_id == 2 || $user->user_types_id == 3) {
        // if ($user->is_approved_provider != 'yes') {
        $user->update([
            'is_approved_provider' => $approve
        ]);
        $approve = Providesrappoverequest::where('customer_id', $user->id)->first();
        if ($approve) {
            $approve->update([
                'approved' => 1
            ]);
        } else {
            $approve->update([
                'approved' => 0
            ]);
        }
        // } else {
        //     return response()->json([
        //         'message' => 'success',
        //         'message_ar' => 'نجاح',
        //         'body' => 'This user is approved before',
        //         'body_ar' => 'هذا المستخدم تمت الموافقة عليه من قبل',
        //         'user' => $user
        //     ], 200);
        // }
        // } else {
        //     return response()->json([
        //         'message' => 'failed',
        //         'message_ar' => 'فشل',
        //         'body' => 'This user is not a provider',
        //         'body_ar' => 'هذا المستخدم ليس بمزود خدمة',
        //         'user' => $user
        //     ], 404);
        // }

        return response()->json([
            'body_en' => 'Provider is approved',
            'message_ar' => 'تمت الموافقة على المزود بنجاح',
            'user' => $user
        ], 200);
    }

    public function deactivateAccountRequests(Request $request)
    {
        $requests = User::with('userType')->where('deactivate_request', 'yes')->where('is_deactive', 1)->orderBy('id', 'desc')->paginate($request->per_page ?: 15);
        foreach ($requests as $req) {
            // User Image
            if ($req && $req->livePhotoFile) {
                $image_name = $req->livePhotoFile->name;
                // dd($image_name);
                // Retrieve the user's profile image if available
                $profileImage = null;
                // Assuming the image is stored in 'storage/app/public/activities'
                $req->live_photo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $req->live_photo = null;
            }

            // Company Logo
            if ($req->company_logo) {
                // $image_name2 = DB::table('files')->find($req->provider->company_logo)->name ?? null;
                $image_name2 = $req->companyLogoFile->name;
                // dd($image_name2);
                // Retrieve the user's company_logo if available
                $company_logo = null;
                // Assuming the image is stored in 'storage/'
                $req->company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
            } else {
                $req->company_logo = null;
            }

            // IBAN Image
            if ($req->an_image) {
                // $path = DB::table('files')->find($user->national_id_image)->path;
                // $iban_image_name = DB::table('files')->find($req->provider->iban_image)->name ?? null;
                $iban_image_name = $req->ibanImageFile->name;
                // Retrieve the user's profile image if available
                $iban_image = null;
                // Assuming the image is stored in 'storage'
                $req->iban_image = env('APP_URL') . 'storage/' . $iban_image_name ? env('APP_URL') . 'storage/' . $iban_image_name : null;
            } else {
                $req->iban_image = null;
            }

            // National Id Image
            if ($req->national_id_image) {
                // $path = DB::table('files')->find($user->national_id_image)->path;
                // $national_id_image_name = DB::table('files')->find($req->provider->national_id_image)->name ?? null;
                $national_id_image_name = $req->nationalIdFile->name;
                // Retrieve the user's profile image if available
                $national_id_image = null;
                // Assuming the image is stored in 'storage'
                $req->national_id_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
            } else {
                $req->national_id_image = null;
            }

            // TRN Image
            if ($req->trn_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                // $trn_image_name = DB::table('files')->find($req->provider->trn_image)->name ?? null;
                $trn_image_name = $req->trnImageFile->name;
                // Retrieve the user's profile image if available
                $trn = null;
                // Assuming the image is stored in 'storage'
                $req->trn_image = env('APP_URL') . 'storage/' . $trn_image_name ? env('APP_URL') . 'storage/' . $trn_image_name : null;
            } else {
                $req->trn_image = null;
            }

            // CR Image
            if ($req->cr_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                // $cr_image_name = DB::table('files')->find($req->provider->cr_image)->name;
                $cr_image_name = $req->provider->crImageFile->name;
                // Retrieve the user's profile image if available
                $cr = null;
                // Assuming the image is stored in 'storage'
                $req->cr_image = env('APP_URL') . 'storage/' . $cr_image_name ? env('APP_URL') . 'storage/' . $cr_image_name : null;
            } else {
                $req->cr_image = null;
            }
        }
        return response()->json([
            'message' => 'success',
            'message_ar' => 'نجاح',
            'requests' => $requests
        ], 200);
    }

    public function providerRequests(Request $request)
    {
        $requests = Providesrappoverequest::with('provider', 'provider.userType')->where('approved', 0)->orderBy('id', 'desc')->paginate($request->per_page ?: 15);
        foreach ($requests as $req) {
            // User Image
            if ($req->provider && $req->provider->livePhotoFile) {
                $image_name = $req->provider->livePhotoFile->name;
                // dd($image_name);
                // Retrieve the user's profile image if available
                $profileImage = null;
                // Assuming the image is stored in 'storage/app/public/activities'
                $req->provider->live_photo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
            } else {
                $req->provider->live_photo = null;
            }

            // Company Logo
            if ($req->provider->company_logo) {
                // $image_name2 = DB::table('files')->find($req->provider->company_logo)->name ?? null;
                $image_name2 = $req->provider->companyLogoFile->name;
                // dd($image_name2);
                // Retrieve the user's company_logo if available
                $company_logo = null;
                // Assuming the image is stored in 'storage/'
                $req->provider->company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
            } else {
                $req->provider->company_logo = null;
            }

            // IBAN Image
            if ($req->provider->iban_image) {
                // $path = DB::table('files')->find($user->national_id_image)->path;
                // $iban_image_name = DB::table('files')->find($req->provider->iban_image)->name ?? null;
                $iban_image_name = $req->provider->ibanImageFile->name;
                // Retrieve the user's profile image if available
                $iban_image = null;
                // Assuming the image is stored in 'storage'
                $req->provider->iban_image = env('APP_URL') . 'storage/' . $iban_image_name ? env('APP_URL') . 'storage/' . $iban_image_name : null;
            } else {
                $req->provider->iban_image = null;
            }

            // National Id Image
            if ($req->provider->national_id_image) {
                // $path = DB::table('files')->find($user->national_id_image)->path;
                // $national_id_image_name = DB::table('files')->find($req->provider->national_id_image)->name ?? null;
                $national_id_image_name = $req->provider->nationalIdFile->name;
                // Retrieve the user's profile image if available
                $national_id_image = null;
                // Assuming the image is stored in 'storage'
                $req->provider->national_id_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
            } else {
                $req->provider->national_id_image = null;
            }

            // TRN Image
            if ($req->provider->trn_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                // $trn_image_name = DB::table('files')->find($req->provider->trn_image)->name ?? null;
                $trn_image_name = $req->provider->trnImageFile->name;
                // Retrieve the user's profile image if available
                $trn = null;
                // Assuming the image is stored in 'storage'
                $req->provider->trn_image = env('APP_URL') . 'storage/' . $trn_image_name ? env('APP_URL') . 'storage/' . $trn_image_name : null;
            } else {
                $req->provider->trn_image = null;
            }

            // Tax Image
            if ($req->provider->tax_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                // $trn_image_name = DB::table('files')->find($req->provider->trn_image)->name ?? null;
                $tax_image_name = $req->provider->taxImageFile->name;
                // Retrieve the user's profile image if available
                $tax = null;
                // Assuming the image is stored in 'storage'
                $req->provider->tax_image = env('APP_URL') . 'storage/' . $tax_image_name ? env('APP_URL') . 'storage/' . $tax_image_name : null;
            } else {
                $req->provider->tax_image = null;
            }

            // CR Image
            if ($req->provider->cr_image) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                // $cr_image_name = DB::table('files')->find($req->provider->cr_image)->name;
                $cr_image_name = $req->provider->crImageFile->name;
                // Retrieve the user's profile image if available
                $cr = null;
                // Assuming the image is stored in 'storage'
                $req->provider->cr_image = env('APP_URL') . 'storage/' . $cr_image_name ? env('APP_URL') . 'storage/' . $cr_image_name : null;
            } else {
                $req->provider->cr_image = null;
            }
        }
        return response()->json([
            'message' => 'success',
            'message_ar' => 'نجاح',
            'requests' => $requests
        ], 200);
    }

    public function providerRequests_details($user_id)
    {
        $request_details = Providesrappoverequest::with('provider')->where('customer_id', $user_id)->first();

        // User Image
        if ($request_details->provider->live_photo) {
            $image_name = DB::table('files')->find($request_details->provider->live_photo)->name ?? null;
            // Retrieve the user's profile image if available
            $profileImage = null;
            // Assuming the image is stored in 'storage/app/public/activities'
            $request_details->provider->live_photo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $request_details->provider->live_photo = null;
        }

        // Company Logo
        if ($request_details->provider->company_logo) {
            $image_name2 = DB::table('files')->find($request_details->provider->company_logo)->name;
            // Retrieve the user's company_logo if available
            $company_logo = null;
            // Assuming the image is stored in 'storage/'
            $request_details->provider->company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
        } else {
            $request_details->provider->company_logo = null;
        }

        // IBAN Image
        if ($request_details->provider->iban_image) {
            // $path = DB::table('files')->find($user->national_id_image)->path;
            $national_id_image_name = DB::table('files')->find($request_details->provider->iban_image)->name ?? null;
            // Retrieve the user's profile image if available
            $iban_image = null;
            // Assuming the image is stored in 'storage'
            $request_details->provider->iban_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
        } else {
            $iban_image = null;
        }

        // National Id Image
        if ($request_details->provider->national_id_image) {
            // $path = DB::table('files')->find($user->national_id_image)->path;
            $national_id_image_name = DB::table('files')->find($request_details->provider->national_id_image)->name ?? null;
            // Retrieve the user's profile image if available
            $national_id_image = null;
            // Assuming the image is stored in 'storage'
            $request_details->provider->national_id_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
        } else {
            $request_details->provider->national_id_image = null;
        }

        // TRN Image
        if ($request_details->provider->trn_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $trn_image_name = DB::table('files')->find($request_details->provider->trn_image)->name;
            // Retrieve the user's profile image if available
            $trn = null;
            // Assuming the image is stored in 'storage'
            $request_details->provider->trn_image = env('APP_URL') . 'storage/' . $trn_image_name ? env('APP_URL') . 'storage/' . $trn_image_name : null;
        } else {
            $request_details->provider->trn_image = null;
        }

        // CR Image
        if ($request_details->provider->cr_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $cr_image_name = DB::table('files')->find($request_details->provider->cr_image)->name;
            // Retrieve the user's profile image if available
            $trn = null;
            // Assuming the image is stored in 'storage'
            $request_details->provider->cr_image = env('APP_URL') . 'storage/' . $cr_image_name ? env('APP_URL') . 'storage/' . $cr_image_name : null;
        } else {
            $request_details->provider->cr_image = null;
        }

        return response()->json([
            'message' => 'success',
            'message_ar' => 'نجاح',
            'requests' => $request_details
        ], 200);
    }

    public function user_details($user_id)
    {
        $user = User::with(['userType', 'activities', 'wishlist', 'bookings'])->find($user_id);
        // dd($user);
        // User Image
        $image_name = DB::table('files')->find($user->live_photo)->name ?? null;
        // Retrieve the user's profile image if available
        $profileImage = null;
        // Assuming the image is stored in 'storage/app/public/activities'
        $user->live_photo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;

        // Company Image
        $image_name2 = DB::table('files')->find($user->company_logo)->name ?? null;
        // Retrieve the user's company_logo if available
        $company_logo = null;
        // Assuming the image is stored in 'storage/'
        $user->company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;

        return response()->json([
            'message' => 'success',
            'message_ar' => 'نجاح',
            'requests' => $user
        ], 200);
    }

    public function dashboard()
    {
        // Current totals
        $allUsers = User::count();
        $providers = User::where('user_types_id', '<>', 1)->count();
        $reservationsThisMonth = Booking::whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
        $toolsOrders = Order::count();

        // Monthly changes (current vs. last month)
        $allUsersThisMonth = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $allUsersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $allUsersChangePercent = $allUsersLastMonth > 0 ? round((($allUsersThisMonth - $allUsersLastMonth) / $allUsersLastMonth) * 100, 2) . ' %' : '0 %';

        $providersThisMonth = User::where('user_types_id', '<>', 1)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $providersLastMonth = User::where('user_types_id', '<>', 1)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $providersChangePercent = $providersLastMonth > 0 ? round((($providersThisMonth - $providersLastMonth) / $providersLastMonth) * 100, 2) . ' %' : '0 %';

        $reservationsLastMonth = Booking::whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->count();
        $reservationsChangePercent = $reservationsLastMonth > 0 ? round((($reservationsThisMonth - $reservationsLastMonth) / $reservationsLastMonth) * 100, 2) . ' %' : '0 %';

        $toolsOrdersThisMonth = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $toolsOrdersLastMonth = Order::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $toolsOrdersChangePercent = $toolsOrdersLastMonth > 0 ? round((($toolsOrdersThisMonth - $toolsOrdersLastMonth) / $toolsOrdersLastMonth) * 100, 2) . ' %' : '0 %';

        // Monthly trends (Jan–Dec)
        $usersGrowthTrend = [];
        $providersGrowthTrend = [];
        $monthlyReservations = [];
        $monthlyToolOrders = [];

        foreach (range(1, 12) as $month) {
            $usersGrowthTrend[] = User::whereMonth('created_at', $month)->whereYear('created_at', now()->year)->count();
            $providersGrowthTrend[] = User::where('user_types_id', '<>', 1)->whereMonth('created_at', $month)->whereYear('created_at', now()->year)->count();
            $monthlyReservations[] = Booking::whereMonth('date', $month)->whereYear('date', now()->year)->count();
            $monthlyToolOrders[] = Order::whereMonth('created_at', $month)->whereYear('created_at', now()->year)->count();
        }

        return response()->json([
            'message' => 'success',
            'data' => [
                'allUsers' => $allUsers,
                'allUsersChangePercent' => $allUsersChangePercent,
                'providers' => $providers,
                'providersChangePercent' => $providersChangePercent,
                'reservationsThisMonth' => $reservationsThisMonth,
                'reservationsChangePercent' => $reservationsChangePercent,
                'toolsOrders' => $toolsOrders,
                'toolsOrdersChangePercent' => $toolsOrdersChangePercent,
                // Chart Data
                'usersGrowthTrend' => $usersGrowthTrend,
                'providersGrowthTrend' => $providersGrowthTrend,
                'monthlyReservations' => $monthlyReservations,
                'monthlyToolOrders' => $monthlyToolOrders,
            ]
        ], 200);
    }
}
