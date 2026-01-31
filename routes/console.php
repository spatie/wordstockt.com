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

Schedule::command(AutoPassExpiredTurnsCommand::class)->runInBackground()->everyFiveMinutes();
Schedule::command(SendTurnReminderNotificationsCommand::class)->runInBackground()->everyFiveMinutes();

Schedule::command(ImportDictionaryWordsCommand::class)->runInBackground()->monthly();
Schedule::command(ImportNlWordDefinitionsCommand::class)->runInBackground()->monthly();
Schedule::command(ImportEnWordDefinitionsCommand::class)->runInBackground()->monthly();

Schedule::command('model:prune', [
    '--model' => [
        Game::class,
        GameInvitation::class,
        GameInviteLink::class,
    ],
])->runInBackground()->daily();

Schedule::command(CleanupInactiveGuestsCommand::class)->runInBackground()->daily();
