<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Game\Models\Game;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloHistory extends Model
{
    protected $table = 'elo_history';

    protected function casts(): array
    {
        return [
            'elo_before' => 'integer',
            'elo_after' => 'integer',
            'elo_change' => 'integer',
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
