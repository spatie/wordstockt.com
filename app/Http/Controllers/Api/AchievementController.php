<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Achievement\Contracts\Achievement;
use App\Domain\Achievement\Models\UserAchievement;
use App\Domain\Achievement\Support\AchievementRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AchievementController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $registry = app(AchievementRegistry::class);

        $unlockedAchievements = $this->getUnlockedAchievements($user->id);
        $allAchievements = $registry->all();

        $achievements = $allAchievements
            ->map(fn (Achievement $achievement) => $this->formatAchievement($achievement, $unlockedAchievements))
            ->values();

        $categories = $this->calculateCategoryCounts($achievements);

        return response()->json([
            'data' => [
                'total_unlocked' => $unlockedAchievements->count(),
                'total_available' => $allAchievements->count(),
                'categories' => $categories,
                'achievements' => $achievements,
            ],
        ]);
    }

    private function getUnlockedAchievements(int $userId): Collection
    {
        return UserAchievement::where('user_id', $userId)
            ->get()
            ->keyBy('achievement_id');
    }

    private function formatAchievement(Achievement $achievement, Collection $unlockedAchievements): array
    {
        $definition = $achievement->definition();
        $userAchievement = $unlockedAchievements->get($achievement->id());

        return [
            'id' => $definition->id,
            'name' => $definition->name,
            'description' => $definition->description,
            'icon' => $definition->icon,
            'category' => $definition->category,
            'is_unlocked' => $userAchievement !== null,
            'unlocked_at' => $userAchievement?->unlocked_at?->toIso8601String(),
            'context' => $userAchievement?->context,
        ];
    }

    private function calculateCategoryCounts(Collection $achievements): array
    {
        return $achievements
            ->groupBy('category')
            ->map(fn (Collection $items) => [
                'unlocked' => $items->where('is_unlocked', true)->count(),
                'total' => $items->count(),
            ])
            ->all();
    }
}
