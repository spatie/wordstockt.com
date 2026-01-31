<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Models\PushToken;
use App\Http\Requests\Auth\StorePushTokenRequest;
use Illuminate\Http\Response;

class PushTokenController
{
    public function __invoke(StorePushTokenRequest $request): Response
    {
        PushToken::updateOrCreate(
            ['token' => $request->validated('token')],
            [
                'user_id' => $request->user()->id,
                'device_name' => $request->validated('device_name'),
            ]
        );

        return response()->noContent();
    }
}
