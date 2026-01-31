<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_admin')
                    ->label('Administrator'),
                TextInput::make('elo_rating')
                    ->numeric()
                    ->disabled(),
                TextInput::make('games_played')
                    ->numeric()
                    ->disabled(),
                TextInput::make('games_won')
                    ->numeric()
                    ->disabled(),
            ]);
    }
}
