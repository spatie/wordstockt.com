<?php

namespace App\Http\Requests\Concerns;

use Closure;

trait ValidatesTileArray
{
    protected function tileRules(): array
    {
        return [
            'tiles' => ['required', 'array', 'min:1'],
            'tiles.*.letter' => ['required', 'string', 'max:2'],
            'tiles.*.x' => ['required', 'integer', 'min:0', 'max:14'],
            'tiles.*.y' => ['required', 'integer', 'min:0', 'max:14'],
            'tiles.*.points' => ['required', 'integer', 'min:0'],
            'tiles.*.is_blank' => ['sometimes', 'boolean', $this->blankTileMustHaveLetter()],
        ];
    }

    protected function blankTileMustHaveLetter(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value) {
                return;
            }

            // Extract index: "tiles.0.is_blank" -> "0"
            $index = explode('.', $attribute)[1];
            $letter = request()->input("tiles.{$index}.letter");

            if ($letter === '*') {
                $fail('Blank tiles must have a letter assigned.');
            }
        };
    }
}
