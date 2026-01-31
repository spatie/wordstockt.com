<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\DeleteUserAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteAccountController
{
    public function __invoke(Request $request, DeleteUserAction $deleteUserAction): JsonResponse
    {
        $deleteUserAction->execute($request->user());

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
