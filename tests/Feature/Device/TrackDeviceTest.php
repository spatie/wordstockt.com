<?php

use App\Domain\User\Models\Device;
use App\Domain\User\Models\PushToken;
use App\Domain\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

function authedRequestWithDeviceHeaders(User $user, array $headers): TestResponse
{
    return test()->actingAs($user, 'sanctum')
        ->withHeaders($headers)
        ->getJson('/api/auth/user');
}

$deviceHeaders = [
    'X-Device-Id' => 'install-uuid-1',
    'X-Platform' => 'ios',
    'X-OS-Version' => '17.4',
    'X-Device-Model' => 'iPhone 15 Pro',
    'X-App-Version' => '1.7.0',
];

it('records the device from request headers', function () use ($deviceHeaders): void {
    $user = User::factory()->create();

    authedRequestWithDeviceHeaders($user, $deviceHeaders)->assertSuccessful();

    $device = Device::query()->where('user_id', $user->id)->sole();

    expect($device->device_id)->toBe('install-uuid-1')
        ->and($device->platform)->toBe('ios')
        ->and($device->os_version)->toBe('17.4')
        ->and($device->model)->toBe('iPhone 15 Pro')
        ->and($device->app_version)->toBe('1.7.0')
        ->and($device->last_seen_at)->not->toBeNull();
});

it('does not record a device when no device id header is sent (old app)', function (): void {
    $user = User::factory()->create();

    test()->actingAs($user, 'sanctum')->getJson('/api/auth/user')->assertSuccessful();

    expect(Device::query()->count())->toBe(0);
});

it('updates the existing device instead of creating a duplicate', function () use ($deviceHeaders): void {
    $user = User::factory()->create();

    authedRequestWithDeviceHeaders($user, $deviceHeaders);
    authedRequestWithDeviceHeaders($user, [...$deviceHeaders, 'X-App-Version' => '1.8.0']);

    $devices = Device::query()->where('user_id', $user->id)->get();

    expect($devices)->toHaveCount(1)
        ->and($devices->first()->app_version)->toBe('1.8.0');
});

it('keeps separate rows per device id for the same user', function () use ($deviceHeaders): void {
    $user = User::factory()->create();

    authedRequestWithDeviceHeaders($user, $deviceHeaders);
    authedRequestWithDeviceHeaders($user, [...$deviceHeaders, 'X-Device-Id' => 'install-uuid-2']);

    expect(Device::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('refreshes last_seen_at only after the throttle window when nothing changed', function () use ($deviceHeaders): void {
    $user = User::factory()->create();

    Carbon::setTestNow('2026-06-07 10:00:00');
    authedRequestWithDeviceHeaders($user, $deviceHeaders);
    $firstSeen = Device::query()->where('user_id', $user->id)->sole()->last_seen_at;

    Carbon::setTestNow('2026-06-07 10:10:00');
    authedRequestWithDeviceHeaders($user, $deviceHeaders);
    expect(Device::query()->where('user_id', $user->id)->sole()->last_seen_at->equalTo($firstSeen))->toBeTrue();

    Carbon::setTestNow('2026-06-07 11:30:00');
    authedRequestWithDeviceHeaders($user, $deviceHeaders);
    expect(Device::query()->where('user_id', $user->id)->sole()->last_seen_at->equalTo($firstSeen))->toBeFalse();

    Carbon::setTestNow();
});

it('does not touch push tokens while tracking a device', function () use ($deviceHeaders): void {
    $user = User::factory()->create();

    authedRequestWithDeviceHeaders($user, $deviceHeaders)->assertSuccessful();

    expect(PushToken::query()->count())->toBe(0);
});
