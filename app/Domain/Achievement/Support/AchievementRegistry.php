<?php

namespace App\Domain\Achievement\Support;

use App\Domain\Achievement\Achievements\Fun\LastTilesWinAchievement;
use App\Domain\Achievement\Achievements\Fun\NeverSwapAchievement;
use App\Domain\Achievement\Achievements\Fun\PalindromeWordAchievement;
use App\Domain\Achievement\Achievements\Fun\StrongOpeningAchievement;
use App\Domain\Achievement\Achievements\GameMilestones\FiftyWinsAchievement;
use App\Domain\Achievement\Achievements\GameMilestones\FirstWinAchievement;
use App\Domain\Achievement\Achievements\GameMilestones\NoPassesAchievement;
use App\Domain\Achievement\Achievements\GameMilestones\TenWinStreakAchievement;
use App\Domain\Achievement\Achievements\Scoring\CenturyAchievement;
use App\Domain\Achievement\Achievements\Scoring\ComebackAchievement;
use App\Domain\Achievement\Achievements\Scoring\DominationAchievement;
use App\Domain\Achievement\Achievements\Scoring\DoubleCenturyAchievement;
use App\Domain\Achievement\Achievements\Scoring\NailBiterAchievement;
use App\Domain\Achievement\Achievements\Streaks\DailySevenAchievement;
use App\Domain\Achievement\Achievements\Streaks\FiveWinStreakAchievement;
use App\Domain\Achievement\Achievements\Streaks\MonthlyMasterAchievement;
use App\Domain\Achievement\Achievements\WordFrequency\PioneerAchievement;
use App\Domain\Achievement\Achievements\WordFrequency\RareFindAchievement;
use App\Domain\Achievement\Achievements\WordFrequency\TrailblazerAchievement;
use App\Domain\Achievement\Achievements\WordFrequency\Vocabulary1000Achievement;
use App\Domain\Achievement\Achievements\WordFrequency\Vocabulary100Achievement;
use App\Domain\Achievement\Achievements\WordFrequency\Vocabulary500Achievement;
use App\Domain\Achievement\Achievements\WordMastery\BingoAchievement;
use App\Domain\Achievement\Achievements\WordMastery\FiftyPointMoveAchievement;
use App\Domain\Achievement\Achievements\WordMastery\SevenLetterWordAchievement;
use App\Domain\Achievement\Achievements\WordMastery\TripleThreatAchievement;
use App\Domain\Achievement\Contracts\Achievement;
use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use Illuminate\Support\Collection;

class AchievementRegistry
{
    private Collection $achievements;

    public function __construct()
    {
        $this->achievements = $this->createAchievements();
    }

    private function createAchievements(): Collection
    {
        return collect([
            // Game Milestones
            new FirstWinAchievement,
            new FiftyWinsAchievement,
            new TenWinStreakAchievement,
            new NoPassesAchievement,

            // Word Mastery
            new SevenLetterWordAchievement,
            new FiftyPointMoveAchievement,
            new BingoAchievement,
            new TripleThreatAchievement,

            // Scoring
            new CenturyAchievement,
            new DoubleCenturyAchievement,
            new DominationAchievement,
            new NailBiterAchievement,
            new ComebackAchievement,

            // Streaks
            new DailySevenAchievement,
            new MonthlyMasterAchievement,
            new FiveWinStreakAchievement,

            // Fun
            new PalindromeWordAchievement,
            new StrongOpeningAchievement,
            new LastTilesWinAchievement,
            new NeverSwapAchievement,

            // Word Frequency
            new PioneerAchievement,
            new TrailblazerAchievement,
            new RareFindAchievement,
            new Vocabulary100Achievement,
            new Vocabulary500Achievement,
            new Vocabulary1000Achievement,
        ])->keyBy(fn (Achievement $achievement) => $achievement->id());
    }

    /** @return Collection<int, MoveTriggerableAchievement> */
    public function getMoveTriggerable(): Collection
    {
        return $this->achievements
            ->filter(fn (Achievement $a) => $a instanceof MoveTriggerableAchievement)
            ->values();
    }

    /** @return Collection<int, GameEndTriggerableAchievement> */
    public function getGameEndTriggerable(): Collection
    {
        return $this->achievements
            ->filter(fn (Achievement $a) => $a instanceof GameEndTriggerableAchievement)
            ->values();
    }

    public function get(string $id): ?Achievement
    {
        return $this->achievements->get($id);
    }

    /** @return Collection<string, Achievement> */
    public function all(): Collection
    {
        return $this->achievements;
    }
}
