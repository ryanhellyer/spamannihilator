<?php

namespace App\Http\Controllers;

use App\Models\UrlMapping;
use App\Services\AnalyticsService;
use App\Services\UrlLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RedirectManagementController extends Controller
{
    public function __construct(
        private UrlLookupService $urlLookupService,
        private AnalyticsService $analyticsService
    ) {
    }

    /**
     * Handle form submission to create a new redirect
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => 'required|url|max:2048',
        ]);

        // Generate a unique random slug
        do {
            $slug = bin2hex(random_bytes(8)); // 16 character hex string
        } while (UrlMapping::where('slug', $slug)->exists());

        // Generate admin hash using HMAC with app key (non-reversible)
        $adminHash = hash_hmac('sha256', $slug, config('app.key'));

        $urlMapping = UrlMapping::create([
            'slug' => $slug,
            'url' => $validated['to'],
            'admin_hash' => $adminHash,
        ]);

        // Clear cache for this slug
        $this->urlLookupService->clearCache($urlMapping->slug);

        // Redirect directly to admin page
        return redirect()->route('admin.show', ['hash' => $adminHash])
            ->with('success', 'Redirect created successfully!');
    }

    /**
     * Show admin page for editing redirect
     */
    public function show(string $hash): View
    {
        $urlMapping = UrlMapping::where('admin_hash', $hash)->firstOrFail();

        // Get hit statistics
        // Database count (synced, persistent)
        $dbHitCount = $urlMapping->hit_count ?? 0;
        
        // Redis count (real-time, unsynced - will be added to DB on next sync)
        $checkPath = '/check/' . $urlMapping->slug;
        $redisHitCount = $this->analyticsService->getHitCount($checkPath);
        
        // Total count (DB + Redis)
        $totalHitCount = $dbHitCount + $redisHitCount;

        return view('admin', [
            'urlMapping' => $urlMapping,
            'adminUrl' => route('admin.show', ['hash' => $hash]),
            'dbHitCount' => $dbHitCount,
            'redisHitCount' => $redisHitCount,
            'totalHitCount' => $totalHitCount,
        ]);
    }

    /**
     * Update redirect from admin page
     */
    public function update(Request $request, string $hash): RedirectResponse
    {
        $urlMapping = UrlMapping::where('admin_hash', $hash)->firstOrFail();

        $validated = $request->validate([
            'from' => 'required|string|max:255|unique:url_mappings,slug,' . $urlMapping->id,
            'to' => 'required|url|max:2048',
            'email' => 'nullable|email|max:255',
        ]);

        $oldSlug = $urlMapping->slug;

        $urlMapping->update([
            'slug' => $validated['from'],
            'url' => $validated['to'],
            'email' => $validated['email'] ?? null,
        ]);

        // Clear cache for both old and new slugs if slug changed
        if ($oldSlug !== $validated['from']) {
            $this->urlLookupService->clearCache($oldSlug);
            $this->urlLookupService->clearCache($validated['from']);
        } else {
            // Clear cache for the slug
            $this->urlLookupService->clearCache($urlMapping->slug);
        }

        return redirect()->route('admin.show', ['hash' => $hash])
            ->with('success', 'Link updated successfully.');
    }
}
