<?php

namespace App\Domain\Support\Data;

use App\Domain\Support\Enums\WordRecommendation;

class WordRecommendationData
{
    public function __construct(
        public WordRecommendation $recommendation,
        public int $confidence,
        public string $reasoning,
    ) {}

    /** @param array{recommendation: string, confidence: int, reasoning: string} $structuredResponse */
    public static function fromStructuredResponse(array $structuredResponse): self
    {
        return new self(
            recommendation: WordRecommendation::from($structuredResponse['recommendation']),
            confidence: (int) $structuredResponse['confidence'],
            reasoning: trim($structuredResponse['reasoning']),
        );
    }
}
