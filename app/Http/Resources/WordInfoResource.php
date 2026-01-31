<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WordInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->getDefinitionData();

        return [
            'word' => $this->word,
            'times_played' => $this->times_played,
            'last_played_at' => $this->last_played_at?->toIso8601String(),
            'definition' => $this->when(! $data->isEmpty(), fn () => [
                'senses' => $data->senses,
                'etymology' => $data->etymology,
                'proverbs' => $data->proverbs,
            ]),
        ];
    }
}
