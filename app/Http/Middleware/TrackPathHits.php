<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPathHits
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Track successful GET requests (200 status)
        // Note: 404s are tracked in the exception handler to catch all 404 cases
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $path = $request->path();
            
            // Skip tracking /checking/{slug} paths - they're already counted via /check/{slug}
            if (preg_match('#^checking/#', $path)) {
                return $response;
            }
            
            // Track normal paths
            $this->analyticsService->trackHit($path);
        }

        return $response;
    }
}
