<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Models\Dictionary;
use Illuminate\View\View;

class InvalidateController
{
    public function __invoke(Dictionary $dictionary): View
    {
        $dictionary->invalidate();

        return view('dictionary.action-confirmed', [
            'action' => 'invalidated',
            'word' => $dictionary->word,
            'language' => $dictionary->language,
        ]);
    }
}
