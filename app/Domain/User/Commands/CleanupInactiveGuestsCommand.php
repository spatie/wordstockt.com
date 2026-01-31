<?php

namespace App\Domain\User\Commands;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;

class CleanupInactiveGuestsCommand extends Command
{
    protected $signature = 'users:cleanup-inactive-guests';

    protected $description = 'Delete guest accounts that have been inactive for more than 60 days and have no active games';

    public function handle(): int
    {
        $cutoffDate = now()->subDays(60);

        $inactiveGuests = User::query()
            ->where('is_guest', true)
            ->where('updated_at', '<', $cutoffDate)
            ->whereDoesntHave('games', function ($query) {
                $query->whereIn('status', [GameStatus::Pending, GameStatus::Active]);
            })
            ->get();

        $count = $inactiveGuests->count();

        $inactiveGuests->each(fn (User $user) => $user->delete());

        $this->info("Deleted {$count} inactive guest accounts.");

        return self::SUCCESS;
    }
}
