<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Actions\RequestWordAdditionAction;
use App\Http\Requests\Dictionary\RequestWordRequest;
use Illuminate\Http\Response;

class RequestWordController
{
    public function __invoke(RequestWordRequest $request): Response
    {
        app(RequestWordAdditionAction::class)->execute(
            word: $request->validated('word'),
            language: $request->validated('language'),
            requester: $request->user(),
        );

        return response()->noContent();
    }
}
