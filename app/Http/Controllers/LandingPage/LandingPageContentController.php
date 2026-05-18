<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\DeleteLandingPageContentRequest;
use App\Http\Requests\LandingPage\LandingPageContentRequest;
use App\Http\Requests\LandingPage\StoreLandingPageContentRequest;
use App\Http\Requests\LandingPage\UpdateLandingPageContentRequest;
use App\Http\Resources\LandingPage\LandingPageContentResource;
use App\Models\LandingPageContent;
use App\Services\LandingPage\LandingPageContentService;
use Illuminate\Http\Request;

class LandingPageContentController extends Controller
{
    public function __construct(
        protected LandingPageContentService $landingPageContentService
    ) {}

    public function saveContent(StoreLandingPageContentRequest $request)
    {
        $validatedData = $request->validated();

        $savedContent = $this->landingPageContentService->saveContent($validatedData);

        return response()->json([
            'data' => new LandingPageContentResource($savedContent),
        ]);
    }

    public function updateContent(UpdateLandingPageContentRequest $request)
    {
        $validatedData = $request->validated();

        $updatedContent = $this->landingPageContentService->updateContent($validatedData);

        if ($updatedContent) {
            return response()->json([
                'data' => new LandingPageContentResource($updatedContent),
            ]);
        }

        return response()->json([
            'message' => 'Page not found or update failed',
            'message_ar' => 'لم يتم العثور على الصفحة أو فشل التحديث',
        ], 404);
    }

    // public function getContentByPageId(LandingPageContentRequest $request)
    // {
    //     $pageContent = $this->landingPageContentService->getContentByPageId($request->page_id);

    //     return response()->json([
    //         'data' => new LandingPageContentResource($pageContent),
    //     ]);
    // }

    public function getContentByPageId($page_id)
    {
        // $pageContent = $this->landingPageContentService->getContentByPageId($request->page_id);
        $pageContent = LandingPageContent::where('page_id', $page_id)->first();
        if ($pageContent) {
            return response()->json([
                'message' => 'success',
                'message_ar' => 'نجاح',
                'data' => new LandingPageContentResource($pageContent),
            ], 200);
        } else {
            return response()->json([
                'message' => 'error not found',
                'message_ar' => 'لم يتم العثور على خطأ',
            ], 404);
        }
    }

    // public function getAllPagesWithContent()
    // {
    //     $data = LandingPageContentResource::collection($this->landingPageContentService->getAllPagesWithContent());

    //     return response()->json([
    //         'data' => $data,
    //         'meta' => ['total' => count($data)],
    //     ]);
    // }

    public function getAllPagesWithContent(Request $request)
    {
        $data = $this->landingPageContentService->getAllPagesWithContent($request);

        return response()->json([
            'data' => $data,
            'meta' => ['total' => count($data)]
        ]);
    }

    public function destroy(DeleteLandingPageContentRequest $request)
    {
        $this->landingPageContentService->deleteActivity($request->page_id);

        return response()->json([
            // 'message' => _('SUCCESS')
            'message' => 'Success',
            'message_ar' => 'نجاح',
        ], 204);
    }
}
