<?php

namespace App\Domain\Support\Enums;

enum DictionaryLanguage: string
{
    case Dutch = 'nl';
    case English = 'en';

    public function label(): string
    {
        return match ($this) {
            self::Dutch => 'Dutch',
            self::English => 'English',
        };
    }

    public function definitionsUrl(): string
    {
        return match ($this) {
            self::Dutch => 'https://kaikki.org/nlwiktionary/raw-wiktextract-data.jsonl.gz',
            self::English => 'https://kaikki.org/dictionary/English/kaikki.org-dictionary-English.jsonl',
        };
    }

    public function isCompressed(): bool
    {
        return match ($this) {
            self::Dutch => true,
            self::English => false,
        };
    }

    public function wiktionaryLanguageIdentifier(): string
    {
        return match ($this) {
            self::Dutch => 'Nederlands',
            self::English => 'English',
        };
    }

    public function etymologyField(): string
    {
        return match ($this) {
            self::Dutch => 'etymology_texts',
            self::English => 'etymology_text',
        };
    }

    public function posField(): string
    {
        return match ($this) {
            self::Dutch => 'pos_title',
            self::English => 'pos',
        };
    }

    public function properNounPos(): string
    {
        return match ($this) {
            self::Dutch => 'Eigennaam',
            self::English => 'name',
        };
    }

    public function downloadTimeout(): int
    {
        return match ($this) {
            self::Dutch => 600,
            self::English => 1800,
        };
    }
}
