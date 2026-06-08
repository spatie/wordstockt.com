<?php

use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('uploads an avatar and returns its url on the user', function (): void {
    $user = User::factory()->create();

    $response = test()->actingAs($user, 'sanctum')
        ->post('/api/auth/user/avatar', [
            'avatar' => UploadedFile::fake()->image('me.jpg', 600, 600),
        ]);

    $response->assertSuccessful();

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1)
        ->and($response->json('data.avatar'))->toBeString()
        ->and($response->json('data.avatar'))->not->toBeEmpty();
});

it('replaces the existing avatar instead of keeping both', function (): void {
    $user = User::factory()->create();

    test()->actingAs($user, 'sanctum')
        ->post('/api/auth/user/avatar', ['avatar' => UploadedFile::fake()->image('first.jpg', 600, 600)])
        ->assertSuccessful();

    test()->actingAs($user, 'sanctum')
        ->post('/api/auth/user/avatar', ['avatar' => UploadedFile::fake()->image('second.jpg', 600, 600)])
        ->assertSuccessful();

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1);
});

it('removes the avatar', function (): void {
    $user = User::factory()->create();

    test()->actingAs($user, 'sanctum')
        ->post('/api/auth/user/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 600, 600)])
        ->assertSuccessful();

    $response = test()->actingAs($user, 'sanctum')->deleteJson('/api/auth/user/avatar');

    $response->assertSuccessful();

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(0)
        ->and($response->json('data.avatar'))->toBeNull();
});

it('rejects a non-image file', function (): void {
    $user = User::factory()->create();

    test()->actingAs($user, 'sanctum')
        ->postJson('/api/auth/user/avatar', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

it('rejects an image larger than 5 MB', function (): void {
    $user = User::factory()->create();

    test()->actingAs($user, 'sanctum')
        ->postJson('/api/auth/user/avatar', [
            'avatar' => UploadedFile::fake()->image('huge.jpg', 600, 600)->size(6000),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

it('requires authentication to upload', function (): void {
    test()->postJson('/api/auth/user/avatar', [
        'avatar' => UploadedFile::fake()->image('me.jpg', 600, 600),
    ])->assertUnauthorized();
});

it('keeps the user resource shape backward compatible when there is no avatar', function (): void {
    $user = User::factory()->create(['avatar_color' => '#E74C3C']);

    $response = test()->actingAs($user, 'sanctum')->getJson('/api/auth/user');

    $response->assertSuccessful()
        ->assertJsonPath('data.avatar', null)
        ->assertJsonPath('data.avatarColor', '#E74C3C');
});
