<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Models\PushToken;
use Illuminate\Http\Request;

class LogoutController
{
    public function __invoke(Request $request)
    {
        if ($pushToken = $request->input('push_token')) {
            PushToken::where('user_id', $request->user()->id)
                ->where('token', $pushToken)
                ->delete();
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
