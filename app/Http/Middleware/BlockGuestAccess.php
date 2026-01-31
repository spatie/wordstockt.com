<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockGuestAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isGuest()) {
            return response()->json([
                'message' => 'This feature is not available for guest accounts. Create a free account to unlock all features.',
                'code' => 'guest_restricted',
            ], 403);
        }

        return $next($request);
    }
}
