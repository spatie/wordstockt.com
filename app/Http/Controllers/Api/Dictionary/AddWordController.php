<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Actions\AddWordToDictionaryAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddWordController
{
    public function __invoke(Request $request): View
    {
        $word = mb_strtoupper(trim($request->query('word', '')));
        $language = $request->query('language', '');

        abort_unless(in_array($language, ['nl', 'en']), 422);
        abort_unless(mb_strlen($word) >= 2, 422);

        app(AddWordToDictionaryAction::class)->execute($word, $language);

        return view('dictionary.action-confirmed', [
            'action' => 'added',
            'word' => $word,
            'language' => $language,
        ]);
    }
}
