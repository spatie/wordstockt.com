<?php

namespace App\Domain\Game\Models;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Support\Models\Concerns\HasUlid;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $turn_expires_at
 * @property-read User|null $winner
 */
class Game extends Model
{
    use HasFactory, HasUlid, Prunable;

    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }

    protected function casts(): array
    {
        return [
            'board_state' => 'array',
            'board_template' => 'array',
            'tile_bag' => 'array',
            'status' => GameStatus::class,
            'consecutive_passes' => 'integer',
            'turn_expires_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public static function turnTimeoutHours(): int
    {
        return 72;
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('status', GameStatus::Pending)
            ->where('created_at', '<=', now()->subWeek());
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_players')
            ->withPivot(['rack_tiles', 'score', 'turn_order'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<GamePlayer, $this>
     */
    public function gamePlayers(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function currentTurnUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_turn_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * @return HasMany<Move, $this>
     */
    public function moves(): HasMany
    {
        return $this->hasMany(Move::class);
    }

    public function latestMove(): HasOne
    {
        return $this->hasOne(Move::class)->latestOfMany();
    }

    public function pendingInvitation(): HasOne
    {
        return $this->hasOne(GameInvitation::class)->where('status', 'pending');
    }

    public function scopeForPlayer(Builder $query, User $user): Builder
    {
        return $query->whereHas('players', fn (Builder $q) => $q->where('users.id', $user->id));
    }

    public function scopeWaitingForMove(Builder $query): Builder
    {
        return $query
            ->where('status', GameStatus::Active)
            ->whereNotNull('turn_expires_at')
            ->whereNotNull('current_turn_user_id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function getGamePlayer(User $user): ?GamePlayer
    {
        return $this->gamePlayers()
            ->where('user_id', $user->id)
            ->first();
    }

    public function hasPlayer(User $user): bool
    {
        return $this->players()->where('users.id', $user->id)->exists();
    }

    public function isFinished(): bool
    {
        return $this->status === GameStatus::Finished;
    }

    public function isActive(): bool
    {
        return $this->status === GameStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === GameStatus::Pending;
    }

    public function isPublicAndPending(): bool
    {
        if (! $this->is_public) {
            return false;
        }

        return $this->isPending();
    }

    public function canBeJoinedBy(User $user): bool
    {
        if ($this->hasPlayer($user)) {
            return false;
        }

        if (! $this->isPending()) {
            return false;
        }

        return $this->gamePlayers()->count() < 2;
    }

    public function isCurrentTurn(User $user): bool
    {
        return $this->current_turn_user_id === $user->id;
    }

    public function shouldNotifyPlayer(?User $player): bool
    {
        if (! $player) {
            return false;
        }

        return $this->isCurrentTurn($player);
    }

    public function isCreator(User $user): bool
    {
        $creator = $this->players()->first();

        if (! $creator) {
            return false;
        }

        return $creator->id === $user->id;
    }

    public function hasRoomForMorePlayers(): bool
    {
        return $this->gamePlayers()->count() < 2;
    }

    public function canBeInvitedToBy(User $user): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        if (! $this->isCreator($user)) {
            return false;
        }

        return $this->hasRoomForMorePlayers();
    }

    public function isLastMoveForPlayer(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->hasPlayer($user)) {
            return false;
        }

        return $this->isLastMoveFor($user->id);
    }

    public function isWinner(User $user): bool
    {
        return $this->winner_id === $user->id;
    }

    public function getOpponent(User $user): ?User
    {
        if (! $this->players->contains('id', $user->id)) {
            return null;
        }

        return $this->players->first(fn (User $player): bool => $player->id !== $user->id);
    }

    public function getPlayerScore(User $user): int
    {
        return $this->gamePlayers->first(fn ($gp): bool => $gp->user_id === $user->id)?->score ?? 0;
    }

    public function getLastMoveDescription(User $forUser, ?User $opponent): string
    {
        $move = $this->latestMove;

        if (! $move) {
            return 'Game Started!';
        }

        $actor = $move->user_id === $forUser->id ? 'You' : ($opponent?->username ?? 'Opponent');

        return match ($move->type) {
            MoveType::Play => $this->formatPlayDescription($move, $actor),
            MoveType::Pass => "{$actor} passed",
            MoveType::Swap => "{$actor} swapped tiles",
            MoveType::Resign => "{$actor} resigned",
        };
    }

    private function formatPlayDescription(Move $move, string $actor): string
    {
        $words = $move->words ?? [];

        if (empty($words)) {
            return "{$actor} played for {$move->score} points";
        }

        $firstWord = $words[0];

        // Handle both formats: strings ['hello'] or objects [['word' => 'hello', 'score' => 8]]
        if (is_array($firstWord)) {
            $word = collect($words)->sortByDesc('score')->first()['word'] ?? '';
        } else {
            $word = collect($words)->sortByDesc(fn ($w): int => strlen((string) $w))->first();
        }

        return "{$actor} played '".strtoupper((string) $word)."' for {$move->score} points";
    }

    public function isLastMoveFor(int $userId): bool
    {
        if (! empty($this->tile_bag)) {
            return false;
        }

        if ($this->current_turn_user_id !== $userId) {
            return false;
        }

        /** @var GamePlayer|null $playerWithEmptyRack */
        $playerWithEmptyRack = $this->gamePlayers
            ->first(fn ($gp): bool => empty($gp->rack_tiles));

        if (! $playerWithEmptyRack) {
            return false;
        }

        return $playerWithEmptyRack->user_id !== $this->current_turn_user_id;
    }

    public function getTurnExpiresAt(): ?Carbon
    {
        if ($this->isFinished()) {
            return null;
        }

        return $this->turn_expires_at;
    }

    public function isTurnExpired(): bool
    {
        if (! $this->turn_expires_at) {
            return false;
        }

        return now()->gte($this->turn_expires_at);
    }

    public function getHoursUntilTurnExpires(): ?int
    {
        $expiresAt = $this->getTurnExpiresAt();

        if (! $expiresAt instanceof \Illuminate\Support\Carbon) {
            return null;
        }

        $hours = (int) now()->diffInHours($expiresAt, false);

        return max(0, $hours);
    }
}
