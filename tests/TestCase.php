<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        RateLimiter::clear('login');
        RateLimiter::clear('register');
        RateLimiter::clear('game-creation');
        RateLimiter::clear('game-invite');
        RateLimiter::clear('friend-request');
        RateLimiter::clear('game-validate');
        RateLimiter::clear('game-move');
        RateLimiter::clear('game-action');
        RateLimiter::clear('search');
        RateLimiter::clear('leaderboard');
        RateLimiter::clear('api');
    }
}
