<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\Device;
use Illuminate\Http\Request;

class TrackDeviceAction
{
    public function execute(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        $deviceId = $request->header('X-Device-Id');

        if (! $deviceId) {
            return;
        }

        $attributes = [
            'platform' => $request->header('X-Platform'),
            'os_version' => $request->header('X-OS-Version'),
            'model' => $request->header('X-Device-Model'),
            'app_version' => $request->header('X-App-Version'),
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
