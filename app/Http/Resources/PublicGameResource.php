<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicGameResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'language' => $this->language,
            'board_template' => $this->board_template,
            'creator' => $this->players->first()?->username,
            'created_at' => $this->created_at,
        ];
    }
}
