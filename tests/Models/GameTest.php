<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Database\Factories\GameFactory;

describe('status helpers', function (): void {
    it('returns true for isFinished when game status is finished', function (): void {
        $game = createGameWithPlayers(status: GameStatus::Finished);

        expect($game->isFinished())->toBeTrue();
        expect($game->isActive())->toBeFalse();
        expect($game->isPending())->toBeFalse();
    });

    it('returns true for isActive when game status is active', function (): void {
        $game = createGameWithPlayers(status: GameStatus::Active);

        expect($game->isActive())->toBeTrue();
        expect($game->isFinished())->toBeFalse();
        expect($game->isPending())->toBeFalse();
    });

    it('returns true for isPending when game status is pending', function (): void {
        $game = createGameWithPlayers(status: GameStatus::Pending);

        expect($game->isPending())->toBeTrue();
        expect($game->isActive())->toBeFalse();
        expect($game->isFinished())->toBeFalse();
    });
});

describe('isCurrentTurn', function (): void {
    it('returns true when it is the users turn', function (): void {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1, player2: $player2);

        expect($game->isCurrentTurn($player1))->toBeTrue();
        expect($game->isCurrentTurn($player2))->toBeFalse();
    });
});

describe('isWinner', function (): void {
    it('returns true when user is the winner', function (): void {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1, player2: $player2, status: GameStatus::Finished);

        $game->update(['winner_id' => $player1->id]);

        expect($game->isWinner($player1))->toBeTrue();
        expect($game->isWinner($player2))->toBeFalse();
    });

    it('returns false when there is no winner', function (): void {
        $player1 = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1);

        expect($game->isWinner($player1))->toBeFalse();
    });
});

describe('getOpponent', function (): void {
    it('returns the opponent for a given user', function (): void {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1, player2: $player2);

        expect($game->getOpponent($player1)->id)->toBe($player2->id);
        expect($game->getOpponent($player2)->id)->toBe($player1->id);
    });

    it('returns null when user is not in the game', function (): void {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $outsider = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1, player2: $player2);

        expect($game->getOpponent($outsider))->toBeNull();
    });
});

describe('getPlayerScore', function (): void {
    it('returns the score for a player', function (): void {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1, player2: $player2);

        $game->gamePlayers()->where('user_id', $player1->id)->update(['score' => 42]);
        $game->gamePlayers()->where('user_id', $player2->id)->update(['score' => 35]);

        $game->refresh();
        $game->load('gamePlayers');

        expect($game->getPlayerScore($player1))->toBe(42);
        expect($game->getPlayerScore($player2))->toBe(35);
    });

    it('returns zero for a player not in the game', function (): void {
        $player1 = User::factory()->create();
        $outsider = User::factory()->create();
        $game = createGameWithPlayers(player1: $player1);

        expect($game->getPlayerScore($outsider))->toBe(0);
    });
});

describe('getTurnExpiresAt', function (): void {
    it('returns the turn expiry time', function (): void {
        $game = createGameWithPlayers();
        $expiresAt = now()->addHours(48);
        $game->update(['turn_expires_at' => $expiresAt]);

        expect($game->getTurnExpiresAt()->timestamp)->toBe($expiresAt->timestamp);
    });

    it('returns null when turn_expires_at is null', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => null]);

        expect($game->getTurnExpiresAt())->toBeNull();
    });

    it('returns null when game is finished', function (): void {
        $game = createGameWithPlayers(status: GameStatus::Finished);
        $game->update(['turn_expires_at' => now()->addHours(48)]);

        expect($game->getTurnExpiresAt())->toBeNull();
    });
});

describe('isTurnExpired', function (): void {
    it('detects expired turns', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => now()->subHour()]);

        expect($game->isTurnExpired())->toBeTrue();
    });

    it('detects non-expired turns', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => now()->addHours(48)]);

        expect($game->isTurnExpired())->toBeFalse();
    });

    it('returns false when turn_expires_at is null', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => null]);

        expect($game->isTurnExpired())->toBeFalse();
    });
});

describe('getHoursUntilTurnExpires', function (): void {
    it('calculates hours until expiry', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => now()->addHours(24)->addMinute()]);

        expect($game->getHoursUntilTurnExpires())->toBe(24);
    });

    it('returns zero when expired', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => now()->subHours(10)]);

        expect($game->getHoursUntilTurnExpires())->toBe(0);
    });

    it('returns null when turn_expires_at is null', function (): void {
        $game = createGameWithPlayers();
        $game->update(['turn_expires_at' => null]);

        expect($game->getHoursUntilTurnExpires())->toBeNull();
    });
});

it('marks pending games older than one week as prunable', function (): void {
    $oldPendingGame = GameFactory::new()->create([
        'status' => GameStatus::Pending,
        'created_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new Game)->prunable()->pluck('id');

    expect($prunableIds)->toContain($oldPendingGame->id);
});

it('does not mark pending games newer than one week as prunable', function (): void {
    $recentPendingGame = GameFactory::new()->create([
        'status' => GameStatus::Pending,
        'created_at' => now()->subDays(3),
    ]);

    $prunableIds = (new Game)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($recentPendingGame->id);
});

it('does not mark active games as prunable regardless of age', function (): void {
    $oldActiveGame = GameFactory::new()->create([
        'status' => GameStatus::Active,
        'created_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new Game)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($oldActiveGame->id);
});

it('does not mark finished games as prunable regardless of age', function (): void {
    $oldFinishedGame = GameFactory::new()->create([
        'status' => GameStatus::Finished,
        'created_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new Game)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($oldFinishedGame->id);
});
