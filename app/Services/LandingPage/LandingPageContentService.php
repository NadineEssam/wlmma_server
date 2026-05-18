<?php

namespace App\Services\LandingPage;

use App\Repositories\LandingPage\LandingPageContentRepository;
use Illuminate\Http\Request;

class LandingPageContentService
{
    public function __construct(
        protected LandingPageContentRepository $landingPageContentRepo
    ) {}

    public function getContentByPageId(string $pageId)
    {
        return $this->landingPageContentRepo->getContentByPageId($pageId);
    }

    // public function getAllPagesWithContent()
    // {
    //     return $this->landingPageContentRepo->getAllPagesWithContent();
    // }

    public function getAllPagesWithContent(Request $request)
    {
        $lang = strtolower($request->header('lang', 'en'));
        $lang = in_array($lang, ['en', 'ar']) ? $lang : 'en';

        $allContent = $this->landingPageContentRepo->getAllPagesWithContent();

        return $allContent->map(function ($item) use ($lang) {
            $content = $item['content'];

            // Handle testimonials if they exist
            $testimonials = [];
            if (!empty($content['testimonials']) && is_array($content['testimonials'])) {
                $testimonials = collect($content['testimonials'])->map(function ($testimonial) use ($lang) {
                    return [
                        'name' => $testimonial["name_{$lang}"] ?? null,
                        'opinion' => $testimonial["opinion_{$lang}"] ?? null,
                        'person_image' => $testimonial['person_image'] ?? null,
                    ];
                });
            }
            $features = [];
            if (!empty($content['features']) && is_array($content['features'])) {
                $features = collect($content['features'])->map(function ($features) use ($lang) {
                    return [
                        'name' => $features["name_{$lang}"] ?? null,
                        // 'opinion' => $features["opinion_{$lang}"] ?? null,
                        // 'person_image' => $features["person_image"] ?? null,
                    ];
                });
            }

            return [
                'page_id' => $item['page_id'],
                'header' => $content["header_{$lang}"] ?? null,
                'title' => $content["title_{$lang}"] ?? null,
                'subtitle' => $content["subtitle_{$lang}"] ?? null,
                'description' => $content["description_{$lang}"] ?? null,
                'image' => $content['image'] ?? null,
                'testimonials' => $testimonials,
                'features' => $features,
            ];
        });
    }

    public function saveContent(array $data)
    {
        return $this->landingPageContentRepo->saveContent($data);
    }

    public function updateContent(array $data)
    {
        return $this->landingPageContentRepo->updateContent($data);
    }

    public function deleteActivity($pageId)
    {
        return $this->landingPageContentRepo->delete($pageId);
    }
}
