<?php

namespace App\Domain\Game\Commands;

use App\Domain\Game\Actions\AutoPassAction;
use App\Domain\Game\Models\Game;
use Illuminate\Console\Command;

class AutoPassExpiredTurnsCommand extends Command
{
    protected $signature = 'games:auto-pass-expired-turns';

    protected $description = 'Auto-pass turns that have expired';

    public function handle(AutoPassAction $autoPassAction): int
    {
        $passed = Game::waitingForMove()
            ->where('turn_expires_at', '<=', now())
            ->get()
            ->each(fn (Game $game) => $autoPassAction->execute($game))
            ->count();

        $this->info("Auto-passed {$passed} expired turns.");

        return self::SUCCESS;
    }
}
