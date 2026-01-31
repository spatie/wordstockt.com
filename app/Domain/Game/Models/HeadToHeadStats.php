<?php

declare(strict_types=1);

namespace App\Domain\Game\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeadToHeadStats extends Model
{
    protected $table = 'head_to_head_stats';

    protected function casts(): array
    {
        return [
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
            'total_score_for' => 'integer',
            'total_score_against' => 'integer',
            'best_word_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    protected function winRate(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $totalGames = $this->wins + $this->losses + $this->draws;

                if ($totalGames === 0) {
                    return 0.0;
                }

                return round(($this->wins / $totalGames) * 100, 1);
            }
        );
    }

    protected function totalGames(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->wins + $this->losses + $this->draws
        );
    }

    protected function averageScoreDifference(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $totalGames = $this->wins + $this->losses + $this->draws;

                if ($totalGames === 0) {
                    return 0.0;
                }

                return round(($this->total_score_for - $this->total_score_against) / $totalGames, 1);
            }
        );
    }
}
