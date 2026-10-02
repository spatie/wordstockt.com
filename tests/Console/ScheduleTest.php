<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @return array<string, string>
 */
function scheduledCommands(): array
{
    return collect(app(Schedule::class)->events())
        ->mapWithKeys(fn (Event $event): array => [
            trim(str($event->command)->after('artisan')->replace(["'", '"'], '')) => $event->expression,
        ])
        ->all();
}

function bootOnLaravelCloud(): void
{
    $_SERVER['LARAVEL_CLOUD'] = '1';

    test()->refreshApplication();
}

afterEach(function (): void {
    unset($_SERVER['LARAVEL_CLOUD']);
});

it('runs the turn tasks every five minutes outside Laravel Cloud', function (): void {
    $commands = scheduledCommands();

    expect($commands['games:auto-pass-expired-turns'])->toBe('*/5 * * * *')
        ->and($commands['games:send-turn-reminders'])->toBe('*/5 * * * *');
});

it('runs the turn tasks hourly on Laravel Cloud', function (): void {
    bootOnLaravelCloud();

    $commands = scheduledCommands();

    expect($commands['games:auto-pass-expired-turns'])->toBe('0 * * * *')
        ->and($commands['games:send-turn-reminders'])->toBe('0 * * * *');
});

it('only schedules the Dutch definitions import on Laravel Cloud', function (): void {
    bootOnLaravelCloud();

    $commands = scheduledCommands();

    expect($commands)->toHaveKey('dictionary:import-definitions nl')
        ->and($commands)->not->toHaveKey('dictionary:import-definitions en')
        ->and(collect($commands)->keys()->filter(fn (string $command): bool => str_contains($command, 'ImportEnWordDefinitionsCommand')))->toBeEmpty();
});
