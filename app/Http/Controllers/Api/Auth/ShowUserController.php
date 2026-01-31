<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ShowUserController
{
    public function __invoke(Request $request): \App\Http\Resources\UserResource
    {
        return new UserResource($request->user());
    }
}
