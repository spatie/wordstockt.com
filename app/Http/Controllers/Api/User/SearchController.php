<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\User\Models\User;
use App\Http\Requests\User\SearchUsersRequest;
use App\Http\Resources\UserSearchResource;

class SearchController
{
    public function __invoke(SearchUsersRequest $request)
    {
        $users = User::searchByUsername($request->validated('query'))
            ->where('id', '!=', $request->user()->id)
            ->select(['id', 'ulid', 'username', 'avatar', 'elo_rating'])
            ->limit(10)
            ->get();

        return UserSearchResource::collection($users);
    }
}
