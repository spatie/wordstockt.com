<?php

namespace App\Domain\Game\Commands;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\TurnReminderNotification;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendTurnReminderNotificationsCommand extends Command
{
    protected $signature = 'games:send-turn-reminders';

    protected $description = 'Send turn reminder notifications to players';

    public function handle(): int
    {
        $sent = collect([24, 4, 1])
            ->flatMap(fn (int $hours) => $this->sendRemindersForHours($hours))
            ->count();

        $this->info("Sent {$sent} turn reminders.");

        return self::SUCCESS;
    }

    private function sendRemindersForHours(int $hours): Collection
    {
        return Game::waitingForMove()
            ->where('turn_expires_at', '>', now()->addHours($hours))
            ->where('turn_expires_at', '<=', now()->addHours($hours + 1))
            ->where(fn ($query) => $query
                ->whereNull('last_turn_reminder_sent')
                ->orWhere('last_turn_reminder_sent', '>', $hours)
            )
            ->with(['currentTurnUser'])
            ->get()
            ->filter(fn (Game $game) => $this->sendReminder($game, $hours));
    }

    private function sendReminder(Game $game, int $hours): bool
    {
        /** @var User|null $player */
        $player = $game->currentTurnUser;

        if (! $player) {
            return false;
        }

        $opponent = $game->getOpponent($player);

        if (! $opponent instanceof User) {
            return false;
        }

        $player->notify(new TurnReminderNotification($game, $hours, $opponent));

        $game->update(['last_turn_reminder_sent' => $hours]);

        return true;
    }
}
