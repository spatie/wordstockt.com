<?php

namespace App\Http\Controllers\Api\Friend;

use App\Domain\User\Exceptions\FriendException;
use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;

class DestroyController
{
    public function __invoke(Request $request, User $user)
    {
        $deleted = Friend::where('user_id', $request->user()->id)
            ->where('friend_id', $user->id)
            ->delete();

        if (! $deleted) {
            throw FriendException::friendNotFound();
        }

        return response()->noContent();
    }
}
