<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackDeviceAction
{
    public function execute(Request $request): void
    {
        $deviceId = $this->boundedHeader($request, 'X-Device-Id', 64);

        if (! $deviceId) {
            return;
        }

        $user = $request->user();

        if (! $user) {
            return;
        }

        $attributes = [
            'platform' => $this->boundedHeader($request, 'X-Platform', 16),
            'os_version' => $this->boundedHeader($request, 'X-OS-Version', 32),
            'model' => $this->boundedHeader($request, 'X-Device-Model', 128),
            'app_version' => $this->boundedHeader($request, 'X-App-Version', 16),
        ];

        $device = Device::query()->firstOrNew([
            'user_id' => $user->id,
            'device_id' => $deviceId,
        ]);

        if (! $this->needsUpdate($device, $attributes)) {
            return;
        }

        $device->fill($attributes);
        $device->last_seen_at = now();
        $device->save();
    }

    private function boundedHeader(Request $request, string $name, int $length): ?string
    {
        $value = $request->header($name);

        if ($value === null) {
            return null;
        }

        return Str::limit($value, $length, '');
    }

    /** @param array<string, ?string> $attributes */
    private function needsUpdate(Device $device, array $attributes): bool
    {
        if (! $device->exists) {
            return true;
        }

        foreach ($attributes as $key => $value) {
            if ($device->{$key} !== $value) {
                return true;
            }
        }

        $lastSeen = $device->last_seen_at;

        return $lastSeen === null || $lastSeen->lt(now()->subHour());
    }
}
