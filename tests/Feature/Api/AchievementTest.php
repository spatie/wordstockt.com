<?php

use App\Domain\Achievement\Models\UserAchievement;
use App\Domain\User\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns all achievements with unlock status', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/achievements');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_unlocked',
                'total_available',
                'categories',
                'achievements' => [
                    '*' => ['id', 'name', 'description', 'icon', 'category', 'is_unlocked', 'unlocked_at', 'context'],
                ],
            ],
        ]);

    expect($response->json('data.total_unlocked'))->toBe(0);
    expect($response->json('data.total_available'))->toBeGreaterThan(0);
});

it('returns empty context as null instead of empty array', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserAchievement::create([
        'user_id' => $user->id,
        'achievement_id' => 'never_swap',
        'context' => [],
        'unlocked_at' => now(),
    ]);

    $response = $this->getJson('/api/achievements');

    $response->assertOk();

    $neverSwap = collect($response->json('data.achievements'))
        ->firstWhere('id', 'never_swap');

    expect($neverSwap['is_unlocked'])->toBeTrue();
    expect($neverSwap['context'])->toBeNull();
});

it('returns context as object when achievement has data', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserAchievement::create([
        'user_id' => $user->id,
        'achievement_id' => 'century',
        'context' => ['score' => 150],
        'unlocked_at' => now(),
    ]);

    $response = $this->getJson('/api/achievements');

    $response->assertOk();

    $century = collect($response->json('data.achievements'))
        ->firstWhere('id', 'century');

    expect($century['is_unlocked'])->toBeTrue();
    expect($century['context'])->toBe(['score' => 150]);
});
