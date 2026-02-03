<?php

namespace App\Filament\Resources\Dictionaries;

use App\Domain\Support\Models\Dictionary;
use App\Filament\Resources\Dictionaries\Pages\ListDictionaries;
use App\Filament\Resources\Dictionaries\Tables\DictionariesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DictionaryResource extends Resource
{
    protected static ?string $model = Dictionary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 3;

    #[\Override]
    public static function table(Table $table): Table
    {
        return DictionariesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDictionaries::route('/'),
        ];
    }
}
