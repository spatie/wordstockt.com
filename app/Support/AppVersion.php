<?php

namespace App\Support;

use Illuminate\Http\Request;

class AppVersion
{
    /**
     * Whether the requesting client is new enough to handle 3-4 player games.
     * The pre-multiplayer app sends no X-App-Version header, so a missing
     * header is treated as unsupported.
     */
    public static function supportsMultiplayer(Request $request): bool
    {
        $version = $request->header('X-App-Version');

        if (! $version) {
            return false;
        }

        return version_compare($version, config('game.min_multiplayer_app_version'), '>=');
    }
}
