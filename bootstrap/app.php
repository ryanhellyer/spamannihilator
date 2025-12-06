<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\TrackPathHits::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('analytics:sync')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Track 404 errors for analytics
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->isMethod('GET')) {
                try {
                    $analyticsService = app(\App\Services\AnalyticsService::class);
                    $analyticsService->trackHit('404');
                } catch (\Exception $trackingError) {
                    // Fail silently - don't break 404 page rendering
                }
            }
            
            // Let Laravel handle the 404 rendering normally
            return null;
        });
    })->create();
