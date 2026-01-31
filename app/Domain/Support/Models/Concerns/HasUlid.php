<?php

namespace App\Domain\Support\Models\Concerns;

use Illuminate\Support\Str;

trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(function ($model): void {
            if (! $model->ulid) {
                $model->ulid = strtolower(Str::ulid()->toString());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
