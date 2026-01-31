<?php

use App\Domain\User\Models\GameInviteLink;
use App\Domain\User\Models\User;
use Database\Factories\GameFactory;

it('marks unused invite links older than one week as prunable', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'linkinviter', 'email' => 'linkinviter@test.com', 'password' => 'password']);

    $oldLink = GameInviteLink::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'used_at' => null,
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new GameInviteLink)->prunable()->pluck('id');

    expect($prunableIds)->toContain($oldLink->id);
});

it('does not mark unused invite links newer than one week as prunable', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'linkinviter2', 'email' => 'linkinviter2@test.com', 'password' => 'password']);

    $recentLink = GameInviteLink::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'used_at' => null,
    ]);

    $prunableIds = (new GameInviteLink)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($recentLink->id);
});

it('does not mark used invite links as prunable regardless of age', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'linkinviter3', 'email' => 'linkinviter3@test.com', 'password' => 'password']);
    $usedBy = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'linkuser', 'email' => 'linkuser@test.com', 'password' => 'password']);

    $oldUsedLink = GameInviteLink::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'used_at' => now()->subWeeks(2),
        'used_by_id' => $usedBy->id,
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new GameInviteLink)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($oldUsedLink->id);
});
