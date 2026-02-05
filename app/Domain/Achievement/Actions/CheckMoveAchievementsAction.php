<?php

namespace App\Domain\Achievement\Actions;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\UnlockedAchievement;
use App\Domain\Achievement\Models\UserAchievement;
use App\Domain\Achievement\Support\AchievementRegistry;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

class CheckMoveAchievementsAction
{
    /** @return Collection<int, UnlockedAchievement> */
    public function execute(User $user, Move $move, Game $game, ScoringResult $scoringResult): Collection
    {
        app(RecordWordPlaysAction::class)->execute($user, $move, $game);

        $user->refresh();

        $existingIds = $this->getExistingAchievementIds($user);

        $unlocked = app(AchievementRegistry::class)->getMoveTriggerable()
            ->reject(fn (MoveTriggerableAchievement $achievement) => $existingIds->has($achievement->id()))
            ->map(fn (MoveTriggerableAchievement $achievement) => $this->checkAchievement($achievement, $user, $move, $game, $scoringResult))
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
        MoveTriggerableAchievement $achievement,
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?UnlockedAchievement {
        $context = $achievement->checkMove($user, $move, $game, $scoringResult);

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
            'context' => $unlocked->context->toJson(),
            'unlocked_at' => now(),
        ])->all();

        UserAchievement::insert($records);
    }
}
