<?php

use App\Domain\Support\Data\WordRecommendationData;
use App\Domain\Support\Enums\WordRecommendation;
use App\Domain\User\Mail\ResetPasswordMail;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\VerifyEmailNotification;
use App\Mail\WordRequestedMail;
use Illuminate\Support\Facades\Route;

Route::prefix('dev/mail')->group(function (): void {
    Route::get('/', fn () => view('dev.mail-index', [
        'mails' => [
            'reset-password' => 'Reset Password',
            'verify-email' => 'Verify Email',
            'word-requested' => 'Word Requested',
        ],
    ]));

    Route::get('/reset-password', function () {
        $user = User::first() ?? new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        return new ResetPasswordMail(
            token: 'sample-reset-token-12345',
            user: $user,
        );
    });

    Route::get('/verify-email', function () {
        $user = User::first() ?? new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'ulid' => '01HTEST123456789ABCDEF',
        ]);

        $notification = new VerifyEmailNotification($user);

        return $notification->toMail($user);
    });

    Route::get('/word-requested', function () {
        $user = User::first() ?? new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $verdict = WordRecommendation::tryFrom(request()->query('verdict', 'add')) ?? WordRecommendation::Add;

        $reasoning = [
            'add' => "Wenen is zowel de infinitief van het werkwoord \"wenen\" (huilen, tranen vergieten) als de Nederlandse naam van de Oostenrijkse hoofdstad.\n\nOmdat minstens één betekenis een gewoon werkwoord is, telt het woord niet als eigennaam en is het geldig in het Nederlands.",
            'reject' => "Amsterdam is uitsluitend de naam van een stad en heeft geen enkele soortnaambetekenis.\n\nEigennamen worden in geen enkele Scrabblewoordenlijst opgenomen, dus dit woord hoort niet in het spel thuis.",
            'uncertain' => "Het woord lijkt een verkleinvorm te zijn, maar ik kan niet vaststellen of deze vorm daadwerkelijk voorkomt in het Nederlands.\n\nOmdat ik de woordenlijsten niet kan raadplegen, laat ik de beslissing liever aan een mens over.",
        ];

        return new WordRequestedMail(
            word: 'WENEN',
            language: 'nl',
            requester: $user,
            recommendation: request()->boolean('without-recommendation') ? null : new WordRecommendationData(
                recommendation: $verdict,
                confidence: 92,
                reasoning: $reasoning[$verdict->value],
            ),
        );
    });
});
