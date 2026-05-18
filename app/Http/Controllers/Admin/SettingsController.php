<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    // Setting details
    public function find($id)
    {
        // Eager load relationships to avoid N+1 queries
        $setting = Setting::find($id);
        return response()->json([
            'message' => 'Success',
            'data' => $setting,
        ], 200);
    }


    // Store setting
    public function store(Request $request)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'describtion_en' => 'nullable|string',
            'describtion_ar' => 'nullable|string',
        ]);

        try {
            $setting = DB::transaction(function () use ($validatedData) {
                return Setting::create([
                    'name_en' => $validatedData['name_en'],
                    'name_ar' => $validatedData['name_ar'],
                    'describtion_en' => $validatedData['describtion_en'] ?? null,
                    'describtion_ar' => $validatedData['describtion_ar'] ?? null,
                ]);
            });

            return response()->json([
                'message' => 'Setting created successfully',
                'message_ar' => 'تم إنشاء الإعداد بنجاح',
                'data' => Setting::find($setting->id),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Setting creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Update setting
    public function update(Request $request, $id)
    {

        $validatedData = $request->validate([
            'name_en' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'describtion_en' => 'sometimes|string',
            'describtion_ar' => 'sometimes|string',
        ]);

        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found.',
                'message_ar' => 'الإعداد غير موجود.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($setting, $validatedData) {
                $setting->update([
                    'name_en' => $validatedData['name_en'] ?? $setting->name_en,
                    'name_ar' => $validatedData['name_ar'] ?? $setting->name_ar,
                    'describtion_en' => $validatedData['describtion_en'] ?? $setting->describtion_en,
                    'describtion_ar' => $validatedData['describtion_ar'] ?? $setting->describtion_ar,
                ]);
            });

            return response()->json([
                'message' => 'Setting updated successfully',
                'message_ar' => 'تم تحديث الإعداد بنجاح',
                'data' => $setting->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Setting update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // delete setting
    public function delete($id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found.',
                'message_ar' => 'الإعداد غير موجود.',
            ], 404);
        }

        try {
            $setting->delete();

            return response()->json([
                'message' => 'Setting deleted successfully',
                'message_ar' => 'تم حذف الإعداد بنجاح',
                'data' => $setting,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Setting deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
