<?php

namespace App\Domain\User\Models;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\Game\Models\Move;
use App\Domain\Support\Models\Concerns\HasUlid;
use App\Domain\User\Mail\ResetPasswordMail;
use App\Domain\User\Notifications\VerifyEmailNotification;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\Expo\ExpoPushToken;

/**
 * @property-read UserStatistics|null $statistics
 */
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUlid, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $attributes = [
        'elo_rating' => 1200,
        'games_played' => 0,
        'games_won' => 0,
        'is_guest' => false,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_guest' => 'boolean',
            'elo_rating' => 'integer',
            'games_played' => 'integer',
            'games_won' => 'integer',
        ];
    }

    public function isGuest(): bool
    {
        return $this->is_guest;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_players')
            ->withPivot(['rack_tiles', 'score', 'turn_order'])
            ->withTimestamps();
    }

    public function gamePlayers(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function moves(): HasMany
    {
        return $this->hasMany(Move::class);
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(UserStatistics::class);
    }

    public function eloHistory(): HasMany
    {
        return $this->hasMany(EloHistory::class)->orderByDesc('created_at');
    }

    public function headToHeadStats(): HasMany
    {
        return $this->hasMany(HeadToHeadStats::class);
    }

    public function gamesWon(): HasMany
    {
        return $this->hasMany(Game::class, 'winner_id');
    }

    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    public function friends(): HasMany
    {
        return $this->hasMany(Friend::class);
    }

    public function getOrCreateStatistics(): UserStatistics
    {
        return $this->statistics()->firstOrCreate(['user_id' => $this->id]);
    }

    public function scopeSearchByUsername(Builder $query, string $prefix): Builder
    {
        return $query->where('username', 'like', $prefix.'%');
    }

    public function scopeForLeaderboard(Builder $query, int $minGamesPlayed = 5): Builder
    {
        return $query->where('is_guest', false)
            ->where('games_played', '>=', $minGamesPlayed)
            ->orderByDesc('elo_rating');
    }

    public function scopeForTimeBasedLeaderboard(Builder $query, int $days): Builder
    {
        $since = Carbon::now()->subDays($days);

        return $query
            ->where('users.is_guest', false)
            ->select([
                'users.id',
                'users.ulid',
                'users.username',
                'users.avatar',
                'users.avatar_color',
                'users.elo_rating',
                'users.games_played',
                'users.games_won',
            ])
            ->selectRaw('COUNT(games.id) as wins_in_period')
            ->join('games', function ($join) use ($since): void {
                $join->on('users.id', '=', 'games.winner_id')
                    ->where('games.status', '=', GameStatus::Finished->value)
                    ->where('games.updated_at', '>=', $since);
            })
            ->groupBy('users.id')
            ->havingRaw('COUNT(games.id) >= 1')
            ->orderByDesc('wins_in_period')
            ->orderByDesc('users.elo_rating');
    }

    public function incrementGamesPlayed(): void
    {
        $this->increment('games_played');
    }

    public function incrementGamesWon(): void
    {
        $this->increment('games_won');
    }

    protected function winRate(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->games_played > 0
                ? round(($this->games_won / $this->games_played) * 100, 1)
                : 0.0
        );
    }

    /** @return array<ExpoPushToken> */
    public function routeNotificationForExpo(): array
    {
        return $this->pushTokens
            ->pluck('token')
            ->map(fn (string $token): \NotificationChannels\Expo\ExpoPushToken => ExpoPushToken::make($token))
            ->all();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification($this));
    }

    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new ResetPasswordMail($token, $this));
    }
}
