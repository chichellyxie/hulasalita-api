<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureVerifiedStudent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Route middleware used in routes/api.php
        $middleware->alias([
            'verified.student' => EnsureVerifiedStudent::class,
            'role' => EnsureRole::class,
        ]);

        // Behind a tunnel or hosting proxy (ngrok, Cloudflare...), trust its
        // forwarded headers so Laravel sees the real https:// address.
        $middleware->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Every midnight (Philippine time) make sure today has a Daily Word Guess.
        // Runs when "php artisan schedule:work" (local) or the server cron is active.
        $schedule->command('puzzles:schedule-daily')
            ->dailyAt('00:01')
            ->timezone('Asia/Manila');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every /api/* error is returned as JSON (401, 403, 404, 422, 429 ...),
        // even if the client forgets the "Accept: application/json" header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Unknown routes and missing records (route model binding) → clean 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });
    })->create();
