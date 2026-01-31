<?php

namespace App\Domain\Support\Data;

class WordDefinitionData
{
    public function __construct(
        public ?array $senses = null,
        public ?string $etymology = null,
        public ?array $proverbs = null,
    ) {}

    public static function fromJson(?string $json): self
    {
        if ($json === null) {
            return new self;
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            return new self;
        }

        return new self(
            senses: $data['senses'] ?? null,
            etymology: $data['etymology'] ?? null,
            proverbs: $data['proverbs'] ?? null,
        );
    }

    public function toJson(): string
    {
        return json_encode([
            'senses' => $this->senses,
            'etymology' => $this->etymology,
            'proverbs' => $this->proverbs,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function isEmpty(): bool
    {
        return $this->senses === null
            && $this->etymology === null
            && $this->proverbs === null;
    }
}
