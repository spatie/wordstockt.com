<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\Dictionary;
use App\Mail\WordRejectedMail;
use Illuminate\Support\Facades\Mail;

class RejectWordAdditionAction
{
    public function execute(string $word, string $language): void
    {
        $word = mb_strtoupper(trim($word));

        $dictionary = Dictionary::query()
            ->where('language', $language)
            ->where('word', $word)
            ->first();

        if (! $dictionary) {
            return;
        }

        if (! $dictionary->requested_by_user_id) {
            return;
        }

        $requester = $dictionary->requestedBy;

        if (! $requester) {
            return;
        }

        Mail::to($requester->email)->send(new WordRejectedMail($word, $language));

        $dictionary->requested_by_user_id = null;
        $dictionary->save();
    }
}
