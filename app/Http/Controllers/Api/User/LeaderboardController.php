<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Enums\LeaderboardType;
use App\Domain\User\Models\User;
use App\Http\Resources\LeaderboardResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Enum;

class LeaderboardController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'type' => ['sometimes', new Enum(LeaderboardType::class)],
        ]);

        $type = LeaderboardType::tryFrom($validated['type'] ?? 'elo') ?? LeaderboardType::Elo;

        $users = $this->getLeaderboard($type);
        $currentUserEntry = $this->getCurrentUserEntry($request->user(), $type, $users);

        return LeaderboardResource::collection($users)
            ->additional([
                'meta' => [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'currentUser' => $currentUserEntry,
                ],
            ]);
    }

    private function getLeaderboard(LeaderboardType $type): Collection
    {
        if ($type === LeaderboardType::Elo) {
            return User::forLeaderboard(minGamesPlayed: 0)
                ->limit(50)
                ->get(['id', 'ulid', 'username', 'avatar', 'avatar_color', 'elo_rating', 'games_played', 'games_won']);
        }

        return User::forTimeBasedLeaderboard($type->days())
            ->limit(50)
            ->get();
    }

    private function getCurrentUserEntry(User $user, LeaderboardType $type, Collection $leaderboard): ?array
    {
        $existingEntry = $leaderboard->firstWhere('id', $user->id);
        if ($existingEntry) {
            return null;
        }

        if ($type === LeaderboardType::Elo) {
            $rank = User::where('elo_rating', '>', $user->elo_rating)->count() + 1;

            return [
                'rank' => $rank,
                'ulid' => $user->ulid,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'avatarColor' => $user->avatar_color,
                'eloRating' => $user->elo_rating,
                'gamesPlayed' => $user->games_played,
                'gamesWon' => $user->games_won,
            ];
        }

        $since = Carbon::now()->subDays($type->days());
        $winsInPeriod = $user->gamesWon()
            ->where('status', GameStatus::Finished)
            ->where('updated_at', '>=', $since)
            ->count();

        if ($winsInPeriod < 1) {
            return [
                'rank' => null,
                'message' => 'Win at least 1 game this period to appear',
                'winsInPeriod' => 0,
            ];
        }

        $rank = User::forTimeBasedLeaderboard($type->days())
            ->havingRaw('COUNT(games.id) > ?', [$winsInPeriod])
            ->count() + 1;

        return [
            'rank' => $rank,
            'ulid' => $user->ulid,
            'username' => $user->username,
            'avatar' => $user->avatar,
            'avatarColor' => $user->avatar_color,
            'eloRating' => $user->elo_rating,
            'winsInPeriod' => $winsInPeriod,
        ];
    }
}
