<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\User\Models\User;
use App\Http\Requests\User\SearchUsersRequest;
use App\Http\Resources\UserSearchResource;

class SearchController
{
    public function __invoke(SearchUsersRequest $request)
    {
        $query = $request->validated('query');
        $exact = $request->boolean('exact', false);

        $usersQuery = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('is_guest', false)
            ->select(['id', 'ulid', 'username', 'avatar', 'avatar_color', 'elo_rating']);

        if ($exact) {
            $user = $usersQuery
                ->whereRaw('LOWER(username) = ?', [strtolower($query)])
                ->first();

            if (! $user) {
                return UserSearchResource::collection(collect());
            }

            return UserSearchResource::collection(collect([$user]));
        }

        $users = $usersQuery
            ->searchByUsername($query)
            ->limit(10)
            ->get();

        return UserSearchResource::collection($users);
    }
}
