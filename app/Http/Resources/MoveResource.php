<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoveResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'type' => $this->when($this->type !== null, fn () => $this->type->value),
            'words' => $this->when($this->words !== null, $this->words),
            'score' => $this->when($this->score !== null, $this->score),
        ];
    }
}
