<?php

namespace App\Filament\Resources\Dictionaries\Tables;

use App\Domain\Support\Models\Dictionary;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DictionariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('word')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language')
                    ->sortable(),
                IconColumn::make('is_valid')
                    ->label('Valid')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('requested_to_mark_as_invalid_at')
                    ->label('Reported')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('times_played')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('language')
                    ->options([
                        'nl' => 'Dutch',
                        'en' => 'English',
                    ]),
                TernaryFilter::make('is_valid')
                    ->label('Validity')
                    ->placeholder('All words')
                    ->trueLabel('Valid words')
                    ->falseLabel('Invalid words'),
                Filter::make('reported')
                    ->label('Reported for review')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('requested_to_mark_as_invalid_at')),
            ])
            ->recordActions([
                Action::make('invalidate')
                    ->label('Mark Invalid')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Mark word as invalid')
                    ->modalDescription(fn (Dictionary $record): string => "Are you sure you want to mark \"{$record->word}\" as invalid? This word will no longer be accepted in gameplay.")
                    ->action(fn (Dictionary $record) => $record->invalidate())
                    ->visible(fn (Dictionary $record): bool => $record->is_valid),
                Action::make('dismiss')
                    ->label('Keep Valid')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Keep word valid')
                    ->modalDescription(fn (Dictionary $record): string => "Are you sure you want to dismiss the report for \"{$record->word}\"? The word will remain valid.")
                    ->action(fn (Dictionary $record) => $record->dismissReport())
                    ->visible(fn (Dictionary $record): bool => $record->is_valid && $record->requested_to_mark_as_invalid_at !== null),
            ])
            ->defaultSort('requested_to_mark_as_invalid_at', 'desc');
    }
}
