<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $current_win_streak
 * @property int $biggest_comeback
 * @property int $unique_words_played
 * @property-read int $games_played
 * @property-read float $average_game_score
 * @property-read float $win_rate
 * @property-read float $first_move_win_rate
 */
class UserStatistics extends Model
{
    protected $table = 'user_statistics';

    protected function casts(): array
    {
        return [
            'games_won' => 'integer',
            'highest_scoring_word_score' => 'integer',
            'highest_scoring_move' => 'integer',
            'bingos_count' => 'integer',
            'longest_word_length' => 'integer',
            'total_words_played' => 'integer',
            'total_points_scored' => 'integer',
            'games_lost' => 'integer',
            'games_draw' => 'integer',
            'highest_game_score' => 'integer',
            'total_game_score' => 'integer',
            'current_win_streak' => 'integer',
            'best_win_streak' => 'integer',
            'biggest_comeback' => 'integer',
            'closest_victory' => 'integer',
            'triple_word_tiles_used' => 'integer',
            'double_word_tiles_used' => 'integer',
            'blank_tiles_played' => 'integer',
            'first_moves_played' => 'integer',
            'first_move_wins' => 'integer',
            'highest_elo_ever' => 'integer',
            'lowest_elo_ever' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function gamesPlayed(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->games_won + $this->games_lost + $this->games_draw
        );
    }

    protected function averageGameScore(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->games_played === 0) {
                    return 0.0;
                }

                return round($this->total_game_score / $this->games_played, 1);
            }
        );
    }

    protected function winRate(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->games_played === 0) {
                    return 0.0;
                }

                return round(($this->games_won / $this->games_played) * 100, 1);
            }
        );
    }

    protected function firstMoveWinRate(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->first_moves_played === 0) {
                    return 0.0;
                }

                return round(($this->first_move_wins / $this->first_moves_played) * 100, 1);
            }
        );
    }
}
