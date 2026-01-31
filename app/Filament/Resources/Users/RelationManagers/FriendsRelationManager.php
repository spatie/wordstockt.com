<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FriendsRelationManager extends RelationManager
{
    protected static string $relationship = 'friends';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('friend'))
            ->columns([
                TextColumn::make('friend.username')
                    ->label('Username')
                    ->getStateUsing(fn ($record) => $record->friend?->username)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('friend.email')
                    ->label('Email')
                    ->getStateUsing(fn ($record) => $record->friend?->email)
                    ->searchable(),
                TextColumn::make('friend.elo_rating')
                    ->label('ELO Rating')
                    ->getStateUsing(fn ($record) => $record->friend?->elo_rating)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Friends Since')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
