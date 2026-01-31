<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Models\Game;
use App\Domain\Support\Models\Dictionary;
use App\Http\Requests\Game\WordInfoRequest;
use App\Http\Resources\WordInfoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WordInfoController
{
    public function __invoke(WordInfoRequest $request, Game $game): AnonymousResourceCollection
    {
        $words = $this->findWordsAtPosition(
            $game->board_state,
            $request->integer('x'),
            $request->integer('y')
        );

        $wordInfos = Dictionary::where('language', $game->language)
            ->whereIn('word', array_map('strtoupper', $words))
            ->get();

        return WordInfoResource::collection($wordInfos);
    }

    private function findWordsAtPosition(array $board, int $x, int $y): array
    {
        return array_values(array_unique(array_filter([
            $this->extractWord($board, $x, $y, horizontal: true),
            $this->extractWord($board, $x, $y, horizontal: false),
        ])));
    }

    private function extractWord(array $board, int $x, int $y, bool $horizontal): ?string
    {
        // Find start of word
        while ($this->hasTileAt($board, $horizontal ? $x - 1 : $x, $horizontal ? $y : $y - 1)) {
            $horizontal ? $x-- : $y--;
        }

        // Collect letters
        $word = '';
        while ($this->hasTileAt($board, $x, $y)) {
            $word .= $board[$y][$x]['letter'];
            $horizontal ? $x++ : $y++;
        }

        return strlen($word) >= 2 ? $word : null;
    }

    private function hasTileAt(array $board, int $x, int $y): bool
    {
        if (! $this->isValidBoardPosition($x, $y)) {
            return false;
        }

        return $board[$y][$x] !== null;
    }

    private function isValidBoardPosition(int $x, int $y): bool
    {
        if ($x < 0 || $x >= 15) {
            return false;
        }

        if ($y < 0 || $y >= 15) {
            return false;
        }

        return true;
    }
}
