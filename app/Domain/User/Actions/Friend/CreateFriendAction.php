<?php

namespace App\Domain\User\Actions\Friend;

use App\Domain\User\Exceptions\FriendException;
use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;

class CreateFriendAction
{
    public function execute(User $user, string $friendUlid): Friend
    {
        $friendUser = User::where('ulid', $friendUlid)->first();

        if (! $friendUser) {
            throw FriendException::userNotFound();
        }

        if ($friendUser->id === $user->id) {
            throw FriendException::cannotAddSelf();
        }

        $existing = Friend::where('user_id', $user->id)
            ->where('friend_id', $friendUser->id)
            ->exists();

        if ($existing) {
            throw FriendException::alreadyFriend();
        }

        return Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friendUser->id,
        ]);
    }
}
