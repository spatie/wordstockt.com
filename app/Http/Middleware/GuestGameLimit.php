<?php

namespace App\Http\Middleware;

use App\Domain\Game\Enums\GameStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestGameLimit
{
    private const MAX_GUEST_GAMES = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isGuest()) {
            return $next($request);
        }

        $activeGameCount = $user->games()
            ->whereIn('status', [GameStatus::Pending, GameStatus::Active])
            ->count();

        if ($activeGameCount >= self::MAX_GUEST_GAMES) {
            return response()->json([
                'message' => 'Guests can play up to 3 games at a time. Create a free account for unlimited games!',
                'code' => 'guest_game_limit',
            ], 403);
        }

        return $next($request);
    }
}
