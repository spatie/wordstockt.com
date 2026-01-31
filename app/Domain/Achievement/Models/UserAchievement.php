<?php

namespace App\Domain\Achievement\Models;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'game_id',
        'context',
        'unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'unlocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
