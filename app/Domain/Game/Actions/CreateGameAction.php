<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Enums\BoardType;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\BoardTemplate;
use App\Domain\Game\Support\TileBag;
use App\Domain\User\Models\User;
use Illuminate\Support\Lottery;

class CreateGameAction
{
    public const int MAX_PENDING_PUBLIC_GAMES = 10;

    public function execute(
        User $creator,
        string $language = 'nl',
        ?string $opponentUsername = null,
        string $boardType = 'standard',
        ?array $customTemplate = null,
        bool $isPublic = false,
    ): Game {
        if ($isPublic) {
            $this->ensureUserCanCreatePublicGame($creator);
        }

        $opponent = $this->resolveOpponent($creator, $opponentUsername);
        $boardTemplate = $this->resolveBoardTemplate($boardType, $customTemplate);

        $tileBag = TileBag::forLanguage($language);

        // When opponent is specified, create a pending game and send invitation
        // This is used for rematch functionality
        $game = Game::create([
            'language' => $language,
            'board_state' => app(Board::class)->createEmptyBoard(),
            'board_template' => $boardTemplate,
            'tile_bag' => $tileBag->toArray(),
            'status' => GameStatus::Pending,
            'current_turn_user_id' => null,
            'is_public' => $isPublic,
        ]);

        $this->addPlayer($game, $creator, $tileBag, turnOrder: 1);

        $game->update(['tile_bag' => $tileBag->toArray()]);

        // If opponent is specified, create an invitation for them
        if ($opponent instanceof \App\Domain\User\Models\User) {
            app(InvitePlayerAction::class)->execute($game, $opponent);
        }

        return $game->fresh(['players', 'gamePlayers', 'pendingInvitation']);
    }

    private function addPlayer(Game $game, User $user, TileBag $tileBag, int $turnOrder): void
    {
        $gamePlayer = GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rack_tiles' => [],
            'score' => 0,
            'turn_order' => $turnOrder,
            'has_received_blank' => false,
        ]);

        $tiles = $tileBag->draw(7);
        $tiles = $this->maybeGiveBlank($tiles, $gamePlayer, $tileBag);
        $gamePlayer->setRackTiles(TileBag::tilesToArray($tiles));
    }

    private function maybeGiveBlank(array $tiles, GamePlayer $gamePlayer, TileBag $tileBag): array
    {
        if ($gamePlayer->has_received_blank) {
            return $tiles;
        }

        if ($tiles === []) {
            return $tiles;
        }

        if (! $this->shouldGiveBlank($tileBag)) {
            return $tiles;
        }

        $gamePlayer->update(['has_received_blank' => true]);

        return $tileBag->swapOneForBlank($tiles);
    }

    private function shouldGiveBlank(TileBag $tileBag): bool
    {
        if ($tileBag->isEmpty()) {
            return true;
        }

        return Lottery::odds(1, 10)->choose();
    }

    private function resolveOpponent(User $creator, ?string $opponentUsername): ?User
    {
        if (! $opponentUsername) {
            return null;
        }

        $opponent = User::where('username', $opponentUsername)->first();

        if (! $opponent) {
            throw GameException::userNotFound();
        }

        if ($opponent->id === $creator->id) {
            throw GameException::cannotPlayAgainstSelf();
        }

        return $opponent;
    }

    private function resolveBoardTemplate(string $boardType, ?array $customTemplate): array
    {
        $type = BoardType::from($boardType);

        if ($this->shouldValidateCustomTemplate($type, $customTemplate)) {
            $this->validateCustomTemplate($customTemplate);

            return $customTemplate;
        }

        return BoardTemplate::fromType($type, $customTemplate);
    }

    private function shouldValidateCustomTemplate(BoardType $type, ?array $customTemplate): bool
    {
        if ($type !== BoardType::Custom) {
            return false;
        }

        return $customTemplate !== null;
    }

    private function validateCustomTemplate(array $customTemplate): void
    {
        $validation = app(ValidateBoardTemplateAction::class)->execute($customTemplate);

        if (! $validation->isValid) {
            throw GameException::invalidBoardTemplate($validation->errors);
        }
    }

    private function ensureUserCanCreatePublicGame(User $user): void
    {
        $pendingPublicGamesCount = Game::query()
            ->where('status', GameStatus::Pending)
            ->where('is_public', true)
            ->whereHas('gamePlayers', fn ($q) => $q->where('user_id', $user->id))
            ->withCount('gamePlayers')
            ->get()
            ->filter(fn (Game $game) => $game->game_players_count === 1)
            ->count();

        if ($pendingPublicGamesCount >= self::MAX_PENDING_PUBLIC_GAMES) {
            throw GameException::maxPublicGamesReached();
        }
    }
}
