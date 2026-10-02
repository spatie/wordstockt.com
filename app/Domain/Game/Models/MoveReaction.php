<?php

namespace App\Domain\Game\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoveReaction extends Model
{
    protected $guarded = [];

    public function move(): BelongsTo
    {
        return $this->belongsTo(Move::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
