<?php

namespace App\Http\Controllers\Api\Friend;

use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;

class CheckController
{
    public function __invoke(Request $request, User $user)
    {
        $isFriend = Friend::where('user_id', $request->user()->id)
            ->where('friend_id', $user->id)
            ->exists();

        return response()->json(['is_friend' => $isFriend]);
    }
}
