<?php

use App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Api\Friend;
use App\Http\Controllers\Api\Game;
use App\Http\Controllers\Api\Invitation;
use App\Http\Controllers\Api\InviteLink;
use App\Http\Controllers\Api\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('auth')->group(function (): void {
    Route::post('register', Auth\RegisterController::class)->middleware('throttle:register');
    Route::post('login', Auth\LoginController::class)->middleware('throttle:login');
    Route::post('guest', Auth\GuestLoginController::class)->middleware('throttle:register');
    Route::post('forgot-password', Auth\ForgotPasswordController::class)->middleware('throttle:login');
    Route::get('verify-email/{ulid}', Auth\VerifyEmailController::class)->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', Auth\LogoutController::class);
        Route::get('user', Auth\ShowUserController::class);
        Route::put('user', Auth\UpdateUserController::class);
        Route::delete('user', Auth\DeleteAccountController::class);
        Route::post('push-token', Auth\PushTokenController::class);
        Route::post('change-password', Auth\ChangePasswordController::class);
        Route::post('resend-verification', Auth\ResendVerificationController::class)->middleware('throttle:6,1');
        Route::post('convert-guest', Auth\ConvertGuestController::class);
    });
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('games')->group(function (): void {
        Route::get('/', Game\IndexController::class);
        Route::post('/', Game\StoreController::class)->middleware(['throttle:game-creation', 'guest.game-limit']);
        Route::get('pending', Game\PendingController::class);
        Route::get('public', Game\PublicGamesController::class);
        Route::get('{game}', Game\ShowController::class);
        Route::delete('{game}', Game\DestroyController::class);
        Route::post('{game}/join', Game\JoinController::class)->middleware('guest.game-limit');
        Route::post('{game}/invite', Game\InviteController::class)->middleware(['throttle:game-invite', 'guest.block']);
        Route::post('{game}/moves', Game\MoveController::class)->middleware('throttle:game-move');
        Route::post('{game}/validate', Game\ValidateController::class)->middleware('throttle:game-validate');
        Route::post('{game}/pass', Game\PassController::class)->middleware('throttle:game-action');
        Route::post('{game}/swap', Game\SwapController::class)->middleware('throttle:game-action');
        Route::post('{game}/resign', Game\ResignController::class)->middleware('throttle:game-action');
        Route::get('{game}/word-info', Game\WordInfoController::class);
    });

    Route::prefix('users')->group(function (): void {
        Route::get('search', User\SearchController::class)->middleware('throttle:search');
        Route::get('leaderboard', User\LeaderboardController::class)->middleware(['throttle:leaderboard', 'guest.block']);
        Route::get('{user}', User\ShowController::class);
        Route::get('{user}/stats', User\StatsController::class)->middleware('guest.block');
        Route::get('{user}/elo-history', User\EloHistoryController::class)->middleware('guest.block');
        Route::get('{user}/head-to-head', User\HeadToHeadController::class)->middleware('guest.block');
    });

    Route::prefix('friends')->middleware('guest.block')->group(function (): void {
        Route::get('/', Friend\IndexController::class);
        Route::get('check/{user}', Friend\CheckController::class);
        Route::post('/', Friend\StoreController::class)->middleware('throttle:friend-request');
        Route::delete('{user}', Friend\DestroyController::class);
    });

    Route::prefix('invitations')->middleware('guest.block')->group(function (): void {
        Route::get('/', Invitation\IndexController::class);
        Route::post('{invitation}/accept', Invitation\AcceptController::class);
        Route::post('{invitation}/decline', Invitation\DeclineController::class);
        Route::delete('{invitation}', Invitation\RevokeController::class);
    });

    Route::post('games/{game}/invite-link', InviteLink\StoreController::class);
    Route::get('invite-links/{code}', InviteLink\ShowController::class);
    Route::post('invite-links/{code}/redeem', InviteLink\RedeemController::class);

    Route::get('achievements', \App\Http\Controllers\Api\AchievementController::class)->middleware('guest.block');
});
