<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\ChangePasswordAction;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;

class ChangePasswordController
{
    public function __invoke(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated('password'));

        return response()->json(['message' => 'Password changed successfully']);
    }
}
