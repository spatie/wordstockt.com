<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimiterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Auth (by IP for unauthenticated endpoints)
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('register', fn ($request) => Limit::perHour(3)->by($request->ip()));

        // Resource creation (by user)
        RateLimiter::for('game-creation', fn ($request) => Limit::perMinute(10)->by($request->user()->id));

        RateLimiter::for('game-invite', fn ($request) => Limit::perMinute(10)->by($request->user()->id));

        RateLimiter::for('friend-request', fn ($request) => Limit::perHour(20)->by($request->user()->id));

        // Game actions (by user)
        RateLimiter::for('game-validate', fn ($request) => Limit::perMinute(30)->by($request->user()->id));

        RateLimiter::for('game-move', fn ($request) => Limit::perMinute(60)->by($request->user()->id));

        RateLimiter::for('game-action', fn ($request) => Limit::perMinute(30)->by($request->user()->id));

        // Query endpoints (by user)
        RateLimiter::for('search', fn ($request) => Limit::perMinute(30)->by($request->user()->id));

        RateLimiter::for('leaderboard', fn ($request) => Limit::perMinute(20)->by($request->user()->id));

        // Global API fallback (by user or IP for unauthenticated)
        RateLimiter::for('api', fn ($request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
    }
}
