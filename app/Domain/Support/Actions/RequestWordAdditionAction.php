<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Mail\WordRequestedMail;
use Illuminate\Support\Facades\Mail;

class RequestWordAdditionAction
{
    public function execute(string $word, string $language, User $requester): void
    {
        $word = mb_strtoupper(trim($word));

        $alreadyExists = Dictionary::query()
            ->where('language', $language)
            ->where('word', $word)
            ->where('is_valid', true)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        Mail::to('freek@spatie.be')->send(new WordRequestedMail($word, $language, $requester));
    }
}
