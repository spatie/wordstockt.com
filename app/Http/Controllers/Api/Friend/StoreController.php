<?php

namespace App\Http\Controllers\Api\Friend;

use App\Domain\User\Actions\Friend\CreateFriendAction;
use App\Http\Requests\User\StoreFriendRequest;
use App\Http\Resources\FriendResource;

class StoreController
{
    public function __invoke(StoreFriendRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $friend = app(CreateFriendAction::class)->execute(
            $request->user(),
            $request->validated('user_ulid')
        );

        return FriendResource::make($friend->load('friend'))
            ->response()
            ->setStatusCode(201);
    }
}
