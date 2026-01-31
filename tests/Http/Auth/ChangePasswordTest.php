<?php

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

it('changes password successfully', function (): void {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertOk()
        ->assertJson(['message' => 'Password changed successfully']);

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

it('fails with incorrect current password', function (): void {
    $user = User::factory()->create([
        'password' => 'correctpassword',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

it('fails when new password is too short', function (): void {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails when password confirmation does not match', function (): void {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails when new password is same as current password', function (): void {
    $user = User::factory()->create([
        'password' => 'samepassword123',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/change-password', [
            'current_password' => 'samepassword123',
            'password' => 'samepassword123',
            'password_confirmation' => 'samepassword123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->postJson('/api/auth/change-password', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(401);
});
