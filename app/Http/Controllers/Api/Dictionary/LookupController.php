<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Models\Dictionary;
use App\Http\Requests\Dictionary\LookupWordRequest;
use App\Http\Resources\WordInfoResource;
use Illuminate\Http\JsonResponse;

class LookupController
{
    public function __invoke(LookupWordRequest $request): JsonResponse
    {
        $word = mb_strtoupper(trim($request->validated('word')));

        $dictionary = Dictionary::query()
            ->where('language', $request->validated('language'))
            ->where('word', $word)
            ->where('is_valid', true)
            ->first();

        if (! $dictionary) {
            return response()->json([
                'found' => false,
                'word' => $word,
                'language' => $request->validated('language'),
            ]);
        }

        return response()->json([
            'found' => true,
            'data' => new WordInfoResource($dictionary),
        ]);
    }
}
