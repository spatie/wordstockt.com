<?php

namespace App\Domain\Support\Data;

use App\Domain\Support\Enums\WordRecommendation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WordRecommendationData
{
    public function __construct(
        public WordRecommendation $recommendation,
        public int $confidence,
        public string $reasoning,
        public ?string $woordenlijstEvidence = null,
    ) {}

    /** @param array<string, mixed> $structuredResponse */
    public static function fromStructuredResponse(array $structuredResponse, ?string $woordenlijstEvidence = null): self
    {
        if (is_string($structuredResponse['reasoning'] ?? null)) {
            $structuredResponse['reasoning'] = Str::squish($structuredResponse['reasoning']);
        }

        $validated = Validator::make($structuredResponse, [
            'recommendation' => ['required', Rule::enum(WordRecommendation::class)],
            'confidence' => ['required', 'integer', 'between:0,100'],
            'reasoning' => ['required', 'string', 'max:400'],
        ])->validate();

        return new self(
            recommendation: WordRecommendation::from($validated['recommendation']),
            confidence: (int) $validated['confidence'],
            reasoning: $validated['reasoning'],
            woordenlijstEvidence: $woordenlijstEvidence,
        );
    }
}
