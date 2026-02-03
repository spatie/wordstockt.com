<?php

namespace App\Domain\Game\Models;

use App\Domain\Game\Enums\MoveType;
use App\Domain\Support\Models\Concerns\HasUlid;
use App\Domain\User\Models\User;
use Database\Factories\MoveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string|array{word: string, score: int}>|null $words
 */
class Move extends Model
{
    use HasFactory, HasUlid;

    protected static function newFactory(): MoveFactory
    {
        return MoveFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tiles' => 'array',
            'words' => 'array',
            'score' => 'integer',
            'score_breakdown' => 'array',
            'type' => MoveType::class,
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPlay(): bool
    {
        return $this->type === MoveType::Play;
    }

    public function isPass(): bool
    {
        return $this->type === MoveType::Pass;
    }

    public function isSwap(): bool
    {
        return $this->type === MoveType::Swap;
    }

    public function isResign(): bool
    {
        return $this->type === MoveType::Resign;
    }
}
