<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Policies\GamePolicy;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Rules\EndGame\ConsecutivePassRule;
use App\Domain\Game\Support\Rules\EndGame\EmptyRackRule;
use App\Domain\Game\Support\Rules\Game\GameActiveRule;
use App\Domain\Game\Support\Rules\Game\SwapLimitRule;
use App\Domain\Game\Support\Rules\Game\TurnOrderRule;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\Game\Support\Rules\Turn\BoardBoundsRule;
use App\Domain\Game\Support\Rules\Turn\CellAvailabilityRule;
use App\Domain\Game\Support\Rules\Turn\ConnectionRule;
use App\Domain\Game\Support\Rules\Turn\FirstMoveCenterRule;
use App\Domain\Game\Support\Rules\Turn\LinePlacementRule;
use App\Domain\Game\Support\Rules\Turn\NoGapsRule;
use App\Domain\Game\Support\Rules\Turn\TilesInRackRule;
use App\Domain\Game\Support\Rules\Turn\WordValidationRule;
use App\Domain\Game\Support\Scoring\Rules\BingoBonusRule;
use App\Domain\Game\Support\Scoring\Rules\EndGameBonusRule;
use App\Domain\Game\Support\Scoring\Rules\LetterScoreRule;
use App\Domain\Game\Support\Scoring\Rules\WordLengthBonusRule;
use App\Domain\Game\Support\Scoring\ScoringEngine;
use App\Domain\Support\Listeners\HandleFailedExpoNotification;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(RuleEngine::class, fn ($app) => new RuleEngine()
            ->addTurnRule(new BoardBoundsRule)
            ->addTurnRule(new CellAvailabilityRule)
            ->addTurnRule(new LinePlacementRule)
            ->addTurnRule(new NoGapsRule)
            ->addTurnRule(new FirstMoveCenterRule)
            ->addTurnRule(new ConnectionRule)
            ->addTurnRule(new TilesInRackRule)
            ->addTurnRule(new WordValidationRule($app->make(Board::class)))
            ->addGameRule(new GameActiveRule)
            ->addGameRule(new TurnOrderRule)
            ->addGameRule(new SwapLimitRule)
            ->addEndGameRule(new EmptyRackRule)
            ->addEndGameRule(new ConsecutivePassRule));

        $this->app->singleton(ScoringEngine::class, fn () => (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new BingoBonusRule)
            ->addRule(new WordLengthBonusRule)
            ->addRule(new EndGameBonusRule));
    }

    public function boot(): void
    {
        Model::unguard();

        Relation::enforceMorphMap([
            'user' => User::class,
        ]);

        Gate::policy(Game::class, GamePolicy::class);

        Event::listen(NotificationFailed::class, HandleFailedExpoNotification::class);
    }
}
