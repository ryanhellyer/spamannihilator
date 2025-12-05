<?php

namespace App\Http\Controllers;

use App\Services\UrlLookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handle the initial check route - redirects to /checking/{slug}
 * Handle the checking route - shows JavaScript redirect page
 */
class RedirectController extends Controller
{
    public function __construct(
        private UrlLookupService $urlLookupService
    ) {
    }

    public function check(string $slug): RedirectResponse
    {
        if (!$this->urlLookupService->slugExists($slug)) {
            abort(404);
        }

        return redirect()->route('checking', ['slug' => $slug], 302);
    }

    public function checking(string $slug): View
    {
        $url = $this->urlLookupService->getUrl($slug);

        if (!$url) {
            abort(404);
        }

        return view('redirect', [
            'redirectUrl' => $url,
            'slug' => $slug,
        ]);
    }
}
