<?php

namespace App\Domain\User\Models;

use App\Domain\Game\Models\Game;
use App\Domain\Support\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GameInviteLink extends Model
{
    use HasUlid, Prunable;

    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('used_at')
            ->where('created_at', '<=', now()->subWeek());
    }

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (GameInviteLink $link): void {
            if (! $link->code) {
                $link->code = strtoupper(Str::random(8));
            }
        });
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_id');
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function markAsUsed(User $user): void
    {
        $this->update([
            'used_at' => now(),
            'used_by_id' => $user->id,
        ]);
    }

    public function getUrl(): string
    {
        return url("/invite/{$this->code}");
    }
}
