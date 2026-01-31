<?php

namespace App\Domain\Achievement\Models;

use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWordPlay extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'dictionary_id',
        'times_played',
        'first_played_at',
    ];

    protected function casts(): array
    {
        return [
            'times_played' => 'integer',
            'first_played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dictionary(): BelongsTo
    {
        return $this->belongsTo(Dictionary::class);
    }
}
