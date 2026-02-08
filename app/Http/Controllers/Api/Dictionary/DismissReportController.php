<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Models\Dictionary;
use Illuminate\View\View;

class DismissReportController
{
    public function __invoke(Dictionary $dictionary): View
    {
        $dictionary->dismissReport();

        return view('dictionary.action-confirmed', [
            'action' => 'dismissed',
            'word' => $dictionary->word,
            'language' => $dictionary->language,
        ]);
    }
}
