<?php

namespace App\Domain\User\Models;

use App\Domain\Game\Models\Game;
use App\Domain\Support\Models\Concerns\HasUlid;
use App\Domain\User\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $invitee_id
 * @property int $inviter_id
 * @property InvitationStatus $status
 * @property-read Game $game
 * @property-read User $inviter
 * @property-read User $invitee
 */
class GameInvitation extends Model
{
    use HasUlid, Prunable;

    public function prunable(): Builder
    {
        return static::query()
            ->where('status', InvitationStatus::Pending)
            ->where('created_at', '<=', now()->subWeek());
    }

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
        ];
    }

    /** @return BelongsTo<Game, $this> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /** @return BelongsTo<User, $this> */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::Pending);
    }

    public function scopeForInvitee(Builder $query, User $user): Builder
    {
        return $query->where('invitee_id', $user->id);
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::Pending;
    }

    public function accept(): void
    {
        $this->update(['status' => InvitationStatus::Accepted]);
    }

    public function decline(): void
    {
        $this->update(['status' => InvitationStatus::Declined]);
    }
}
