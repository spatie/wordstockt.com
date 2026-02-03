<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Models\Dictionary;
use Illuminate\Http\RedirectResponse;

class InvalidateController
{
    public function __invoke(Dictionary $dictionary): RedirectResponse
    {
        $dictionary->invalidate();

        return redirect()->to(config('app.url').'/admin/dictionaries')
            ->with('success', "Word '{$dictionary->word}' has been marked as invalid.");
    }
}
