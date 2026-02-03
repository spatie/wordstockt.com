<?php

namespace App\Filament\Resources\Dictionaries\Pages;

use App\Filament\Resources\Dictionaries\DictionaryResource;
use Filament\Resources\Pages\ListRecords;

class ListDictionaries extends ListRecords
{
    protected static string $resource = DictionaryResource::class;
}
