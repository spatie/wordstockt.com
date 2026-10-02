<?php

use App\Domain\Game\Commands\AutoPassExpiredTurnsCommand;
use App\Domain\Game\Commands\SendTurnReminderNotificationsCommand;
use App\Domain\Game\Models\Game;
use App\Domain\Support\Commands\Dictionary\ImportDictionaryWordsCommand;
use App\Domain\Support\Commands\Dictionary\ImportEnWordDefinitionsCommand;
use App\Domain\Support\Commands\Dictionary\ImportNlWordDefinitionsCommand;
use App\Domain\User\Commands\CleanupInactiveGuestsCommand;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\GameInviteLink;
use Illuminate\Support\Facades\Schedule;

/*
 * On Laravel Cloud the app and database scale to zero after five minutes without
 * traffic. Running the turn tasks every fifteen minutes lets them sleep in between.
 * Turns last 72 hours and reminders use one hour windows, so this is precise enough.
 */
$turnTasksCron = laravel_cloud() ? '*/15 * * * *' : '*/5 * * * *';

Schedule::command(AutoPassExpiredTurnsCommand::class)->runInBackground()->cron($turnTasksCron);
Schedule::command(SendTurnReminderNotificationsCommand::class)->runInBackground()->cron($turnTasksCron);

Schedule::command(ImportDictionaryWordsCommand::class)->runInBackground()->monthly();

/*
 * The English definitions import runs longer than Laravel Cloud keeps a sleeping
 * app awake, so on Cloud it is run by hand: `php artisan dictionary:import-definitions en`.
 */
if (laravel_cloud()) {
    Schedule::command('dictionary:import-definitions nl')->runInBackground()->monthly();
} else {
    Schedule::command(ImportNlWordDefinitionsCommand::class)->runInBackground()->monthly();
    Schedule::command(ImportEnWordDefinitionsCommand::class)->runInBackground()->monthly();
}

Schedule::command('model:prune', [
    '--model' => [
        Game::class,
        GameInvitation::class,
        GameInviteLink::class,
    ],
])->runInBackground()->daily();

Schedule::command(CleanupInactiveGuestsCommand::class)->runInBackground()->daily();
