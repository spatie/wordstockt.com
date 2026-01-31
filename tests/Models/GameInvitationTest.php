<?php

use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;
use Database\Factories\GameFactory;

it('marks pending invitations older than one week as prunable', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'inviter', 'email' => 'inviter@test.com', 'password' => 'password']);
    $invitee = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'invitee', 'email' => 'invitee@test.com', 'password' => 'password']);

    $oldInvitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new GameInvitation)->prunable()->pluck('id');

    expect($prunableIds)->toContain($oldInvitation->id);
});

it('does not mark pending invitations newer than one week as prunable', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'inviter2', 'email' => 'inviter2@test.com', 'password' => 'password']);
    $invitee = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'invitee2', 'email' => 'invitee2@test.com', 'password' => 'password']);

    $recentInvitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $prunableIds = (new GameInvitation)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($recentInvitation->id);
});

it('does not mark accepted invitations as prunable regardless of age', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'inviter3', 'email' => 'inviter3@test.com', 'password' => 'password']);
    $invitee = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'invitee3', 'email' => 'invitee3@test.com', 'password' => 'password']);

    $oldAcceptedInvitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Accepted,
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new GameInvitation)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($oldAcceptedInvitation->id);
});

it('does not mark declined invitations as prunable regardless of age', function (): void {
    $game = GameFactory::new()->create();
    $inviter = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'inviter4', 'email' => 'inviter4@test.com', 'password' => 'password']);
    $invitee = User::create(['ulid' => strtolower(\Illuminate\Support\Str::ulid()), 'username' => 'invitee4', 'email' => 'invitee4@test.com', 'password' => 'password']);

    $oldDeclinedInvitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Declined,
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $prunableIds = (new GameInvitation)->prunable()->pluck('id');

    expect($prunableIds)->not->toContain($oldDeclinedInvitation->id);
});
