<?php

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Exceptions\InvalidMoveException;
use App\Domain\User\Exceptions\FriendException;
use App\Http\Middleware\BlockGuestAccess;
use App\Http\Middleware\GuestGameLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\RateLimiterServiceProvider::class,
    ])
    ->withCommands([
        __DIR__.'/../app/Domain/Game/Commands',
        __DIR__.'/../app/Domain/Support/Commands',
        __DIR__.'/../app/Domain/User/Commands',
        __DIR__.'/../app/Console/Commands/RayDemo',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            if (app()->environment('local')) {
                Route::middleware('web')
                    ->group(base_path('routes/development.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest.block' => BlockGuestAccess::class,
            'guest.game-limit' => GuestGameLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Spatie\LaravelFlare\Facades\Flare::handles($exceptions);

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Please wait a moment before trying again.',
            ], 429)->withHeaders($exception->getHeaders());
        });

        $exceptions->render(function (FriendException|GameException|InvalidMoveException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => $exception->getMessage()], $exception->statusCode);
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'An error occurred',
            ], $exception->getStatusCode());
        });
    })->create();
