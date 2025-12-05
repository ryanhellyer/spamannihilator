<?php

namespace App\Http\Controllers;

use App\Models\UrlMapping;
use App\Services\UrlLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RedirectManagementController extends Controller
{
    public function __construct(
        private UrlLookupService $urlLookupService
    ) {
    }

    /**
     * Handle form submission to create a new redirect
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:255|unique:url_mappings,slug',
            'to' => 'required|url|max:2048',
        ]);

        // Generate admin hash using HMAC with app key (non-reversible)
        $adminHash = hash_hmac('sha256', $validated['from'], config('app.key'));

        $urlMapping = UrlMapping::create([
            'slug' => $validated['from'],
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

        return view('admin', [
            'urlMapping' => $urlMapping,
            'adminUrl' => route('admin.show', ['hash' => $hash]),
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

        // If slug changed, regenerate admin hash
        if ($oldSlug !== $validated['from']) {
            $newHash = hash_hmac('sha256', $validated['from'], config('app.key'));
            $urlMapping->update(['admin_hash' => $newHash]);
            
            // Clear cache for both old and new slugs
            $this->urlLookupService->clearCache($oldSlug);
            $this->urlLookupService->clearCache($validated['from']);

            return redirect()->route('admin.show', ['hash' => $newHash])
                ->with('success', 'Redirect updated successfully. Please save your new admin URL.');
        }

        // Clear cache for the slug
        $this->urlLookupService->clearCache($urlMapping->slug);

        return redirect()->route('admin.show', ['hash' => $hash])
            ->with('success', 'Redirect updated successfully.');
    }
}
