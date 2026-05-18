<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaqsController extends Controller
{
    // All Faq
    public function index(Request $request)
    {
        // Eager load relationships to avoid N+1 queries
        $Faq = Faq::paginate($request->per_page);
        return response()->json([
            'message' => 'Success',
            'data' => $Faq,
        ], 200);
    }

    // Faq details
    public function find($id)
    {
        // Eager load relationships to avoid N+1 queries
        $Faq = Faq::find($id);
        return response()->json([
            'message' => 'Success',
            'data' => $Faq,
        ], 200);
    }

    // Store Faq
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
            $Faq = DB::transaction(function () use ($validatedData) {
                return Faq::create([
                    'name_en' => $validatedData['name_en'],
                    'name_ar' => $validatedData['name_ar'],
                    'describtion_en' => $validatedData['describtion_en'] ?? null,
                    'describtion_ar' => $validatedData['describtion_ar'] ?? null,
                ]);
            });

            return response()->json([
                'message' => 'Faq created successfully',
                'message_ar' => 'تم إنشاء الإعداد بنجاح',
                'data' => Faq::find($Faq->id),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Faq creation failed', [
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

    // Update Faq
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name_en' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'describtion_en' => 'sometimes|string',
            'describtion_ar' => 'sometimes|string',
        ]);

        $Faq = Faq::find($id);

        if (!$Faq) {
            return response()->json([
                'message' => 'Faq not found.',
                'message_ar' => 'الإعداد غير موجود.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($Faq, $validatedData) {
                $Faq->update([
                    'name_en' => $validatedData['name_en'] ?? $Faq->name_en,
                    'name_ar' => $validatedData['name_ar'] ?? $Faq->name_ar,
                    'describtion_en' => $validatedData['describtion_en'] ?? $Faq->describtion_en,
                    'describtion_ar' => $validatedData['describtion_ar'] ?? $Faq->describtion_ar,
                ]);
            });

            return response()->json([
                'message' => 'Faq updated successfully',
                'message_ar' => 'تم تحديث الإعداد بنجاح',
                'data' => $Faq->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Faq update failed', [
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

    // delete Faq
    public function delete($id)
    {
        $Faq = Faq::find($id);

        if (!$Faq) {
            return response()->json([
                'message' => 'Faq not found.',
                'message_ar' => 'الإعداد غير موجود.',
            ], 404);
        }

        try {
            $Faq->delete();

            return response()->json([
                'message' => 'Faq deleted successfully',
                'message_ar' => 'تم حذف الإعداد بنجاح',
                'data' => $Faq,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Faq deletion failed', [
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
