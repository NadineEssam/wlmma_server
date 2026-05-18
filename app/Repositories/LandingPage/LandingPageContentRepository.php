<?php

namespace App\Repositories\LandingPage;

use App\Models\LandingPageContent;

class LandingPageContentRepository
{
    public function getContentByPageId(string $pageId)
    {
        return LandingPageContent::where('page_id', $pageId)->first();
    }

    // public function getAllPagesWithContent()
    // {
    //     return LandingPageContent::all(['page_id', 'content']);
    // }

    public function getAllPagesWithContent()
    {
        return LandingPageContent::orderBy('page_id', 'asc')->get(['page_id', 'content']);
    }

    public function saveContent(array $data)
    {
        return LandingPageContent::create([
            'page_id' => $data['page_id'],
            'content' => $data['content'],
        ]);
    }

    public function updateContent(array $data)
    {
        $landingPageContent = LandingPageContent::where('page_id', $data['page_id'])->first();

        if ($landingPageContent) {
            $landingPageContent->update([
                'content' => $data['content'],
            ]);

            return $landingPageContent;
        }

        return null;
    }

    public function delete($pageId)
    {
        return LandingPageContent::where('page_id', $pageId)->delete();
    }
}
