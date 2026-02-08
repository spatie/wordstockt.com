<?php

use App\Http\Controllers\Api\Dictionary\AddWordController;
use App\Http\Controllers\Api\Dictionary\DismissReportController;
use App\Http\Controllers\Api\Dictionary\InvalidateController;
use App\Http\Controllers\AppleAppSiteAssociationController;
use App\Http\Controllers\AssetLinksController;
use App\Http\Controllers\Web\InviteLinkRedirectController;
use App\Http\Controllers\Web\ResetPasswordController;
use App\Http\Controllers\Web\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::get('/privacy', fn () => view('privacy'));
Route::get('/support', fn () => view('support'));
Route::get('/delete-account', fn () => view('delete-account'));
Route::get('/og-image', fn () => view('og-image'));
Route::get('/feature-graphic', fn () => view('feature-graphic'));
Route::get('.well-known/apple-app-site-association', AppleAppSiteAssociationController::class);
Route::get('.well-known/assetlinks.json', AssetLinksController::class);

Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password/{token}', [ResetPasswordController::class, 'update'])->name('password.update');

Route::get('/verify-email/{ulid}', VerifyEmailController::class)->name('verification.verify.web');

Route::get('/invite/{code}', InviteLinkRedirectController::class)->name('invite.redirect');

Route::middleware('signed')->group(function () {
    Route::get('/dictionary/{dictionary}/invalidate', InvalidateController::class)->name('dictionary.invalidate');
    Route::get('/dictionary/{dictionary}/dismiss', DismissReportController::class)->name('dictionary.dismiss');
    Route::get('/dictionary/add-word', AddWordController::class)->name('dictionary.add-word');
});
