<?php

declare(strict_types=1);

namespace App\Domain\Game\Data;

use Illuminate\Support\Collection;
use JsonSerializable;

readonly class PlacementValidationResult implements JsonSerializable
{
    /**
     * @param  Collection<int, string>  $placementErrors
     * @param  Collection<int, ValidatedWord>  $words
     * @param  Collection<int, TileStatus>  $tileStatus
     */
    public function __construct(
        public bool $placementValid,
        public Collection $placementErrors,
        public Collection $words,
        public Collection $tileStatus,
        public ?int $potentialScore = null,
    ) {}

    public function allWordsValid(): bool
    {
        return $this->words->every(fn (ValidatedWord $word): bool => $word->valid);
    }

    public function isFullyValid(): bool
    {
        if (! $this->placementValid) {
            return false;
        }

        return $this->allWordsValid();
    }

    public function jsonSerialize(): array
    {
        return [
            'placement_valid' => $this->placementValid,
            'placement_errors' => $this->placementErrors->all(),
            'words' => $this->words->all(),
            'tile_status' => $this->tileStatus->all(),
            'potential_score' => $this->potentialScore,
        ];
    }
}
