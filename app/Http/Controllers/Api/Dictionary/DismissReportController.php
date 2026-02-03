<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Models\Dictionary;
use Illuminate\Http\RedirectResponse;

class DismissReportController
{
    public function __invoke(Dictionary $dictionary): RedirectResponse
    {
        $dictionary->dismissReport();

        return redirect()->to(config('app.url').'/admin/dictionaries')
            ->with('success', "Report for '{$dictionary->word}' has been dismissed.");
    }
}
