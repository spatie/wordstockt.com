<?php

namespace App\Http\Controllers\Api\Friend;

use App\Domain\User\Models\Friend;
use App\Http\Resources\FriendResource;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $friends = Friend::where('user_id', $request->user()->id)
            ->with('friend')
            ->get();

        return FriendResource::collection($friends);
    }
}
