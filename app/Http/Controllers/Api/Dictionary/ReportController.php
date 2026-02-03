<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Models\Dictionary;
use App\Http\Requests\Dictionary\ReportWordRequest;
use App\Mail\WordReportedMail;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class ReportController
{
    public function __invoke(ReportWordRequest $request): Response
    {
        $word = mb_strtoupper(trim($request->validated('word')));

        $dictionary = Dictionary::query()
            ->where('language', $request->validated('language'))
            ->where('word', $word)
            ->where('is_valid', true)
            ->first();

        if (! $dictionary) {
            abort(404);
        }

        $alreadyReported = $dictionary->requested_to_mark_as_invalid_at !== null;

        $dictionary->requestInvalidation();

        if (! $alreadyReported) {
            Mail::to('freek@spatie.be')->send(new WordReportedMail($dictionary, $request->user()));
        }

        return response()->noContent();
    }
}
