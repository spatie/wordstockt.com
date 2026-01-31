<?php

namespace App\Domain\Achievement\Actions;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\UnlockedAchievement;
use App\Domain\Achievement\Models\UserAchievement;
use App\Domain\Achievement\Support\AchievementRegistry;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

class CheckGameEndAchievementsAction
{
    /** @return Collection<int, UnlockedAchievement> */
    public function execute(User $user, Game $game): Collection
    {
        $user->refresh();
        $user->load('statistics');

        $existingIds = $this->getExistingAchievementIds($user);

        $unlocked = app(AchievementRegistry::class)->getGameEndTriggerable()
            ->reject(fn (GameEndTriggerableAchievement $achievement) => $existingIds->has($achievement->id()))
            ->map(fn (GameEndTriggerableAchievement $achievement) => $this->checkAchievement($achievement, $user, $game))
            ->filter()
            ->values();

        $this->saveUnlockedAchievements($unlocked, $user, $game);

        return $unlocked;
    }

    private function getExistingAchievementIds(User $user): Collection
    {
        return UserAchievement::where('user_id', $user->id)
            ->pluck('achievement_id')
            ->flip();
    }

    private function checkAchievement(
        GameEndTriggerableAchievement $achievement,
        User $user,
        Game $game,
    ): ?UnlockedAchievement {
        $context = $achievement->checkGameEnd($user, $game);

        if (! $context) {
            return null;
        }

        return new UnlockedAchievement($achievement, $context);
    }

    /** @param Collection<int, UnlockedAchievement> $unlocked */
    private function saveUnlockedAchievements(Collection $unlocked, User $user, Game $game): void
    {
        if ($unlocked->isEmpty()) {
            return;
        }

        $records = $unlocked->map(fn (UnlockedAchievement $unlocked) => [
            'user_id' => $user->id,
            'achievement_id' => $unlocked->achievement->id(),
            'game_id' => $game->id,
            'context' => json_encode($unlocked->context->toArray()),
            'unlocked_at' => now(),
        ])->all();

        UserAchievement::insert($records);
    }
}
