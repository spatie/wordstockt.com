<?php

namespace App\Domain\Support\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use SimpleXMLElement;

class LookupWordOnWoordenlijstAction
{
    public function execute(string $word): string
    {
        $word = mb_strtolower(trim($word));
        $unavailable = 'Opzoeken niet gelukt; controleer de bron handmatig.';

        if (! preg_match('/\A[a-z]+\z/', $word)) {
            return $unavailable;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)
                ->get('https://woordenlijst.org/MolexServe/lexicon/find_wordform', [
                    'database' => 'gig_pro_wrdlst',
                    'wordform' => $word,
                    'paradigm' => 'true',
                    'diminutive' => 'false',
                    'onlyvalid' => 'true',
                    'regex' => 'false',
                ]);
        } catch (ConnectionException) {
            return $unavailable;
        }

        if (! $response->successful()) {
            return $unavailable;
        }

        $xml = $this->parseXml($response->body());

        if (! $xml || $xml->getName() !== 'root' || (string) $xml->message !== 'OK' || mb_strtolower((string) $xml->wordform) !== $word) {
            return $unavailable;
        }

        $matches = collect();

        foreach ($xml->found_lemmata as $entry) {
            if ((string) $entry->message !== 'OK' || blank((string) $entry->lemma) || blank((string) $entry->label) || ! isset($entry->paradigm->paradigm)) {
                return $unavailable;
            }

            foreach ($entry->paradigm->paradigm as $form) {
                if (mb_strtolower((string) $form->wordform) !== $word) {
                    continue;
                }

                $inflection = Str::squish("{$form->group_label} {$form->label}");
                $gloss = filled((string) $entry->gloss) ? "; {$entry->gloss}" : '';
                $trademark = (string) $entry->trademark === 'true' ? '; handelsmerk' : '';

                $matches->push(Str::squish("\"{$form->wordform}\" — {$inflection} van \"{$entry->lemma}\" ({$entry->label}{$gloss}{$trademark})"));
            }
        }

        if ($matches->isEmpty()) {
            return 'Geen exacte woordvorm gevonden; dit bewijst niet dat het woord ongeldig is.';
        }

        return 'Gevonden: '.$matches->unique()->implode('; ').'.';
    }

    private function parseXml(string $body): ?SimpleXMLElement
    {
        if (str_contains(strtoupper($body), '<!DOCTYPE')) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            return simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET) ?: null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
