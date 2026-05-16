<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Actions\RejectWordAdditionAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RejectWordController
{
    public function __invoke(Request $request): View
    {
        $word = mb_strtoupper(trim($request->query('word', '')));
        $language = $request->query('language', '');

        abort_unless(in_array($language, ['nl', 'en']), 422);
        abort_unless(mb_strlen($word) >= 2, 422);

        app(RejectWordAdditionAction::class)->execute($word, $language);

        return view('dictionary.action-confirmed', [
            'action' => 'rejected',
            'word' => $word,
            'language' => $language,
        ]);
    }
}
