<?php

namespace App\Filament\Resources\Games\Tables;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Game $record): string => route('filament.admin.resources.games.view', ['record' => $record->ulid]))
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['players', 'gamePlayers', 'currentTurnUser', 'winner', 'latestMove'])
                ->latest('games.updated_at')
            )
            ->columns([
                TextColumn::make('players')
                    ->label('Players')
                    ->getStateUsing(function (Game $record) {
                        $players = $record->players->pluck('username')->toArray();

                        return implode(' vs ', $players);
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('players', function ($q) use ($search) {
                            $q->where('username', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (GameStatus $state): string => match ($state) {
                        GameStatus::Pending => 'warning',
                        GameStatus::Active => 'success',
                        GameStatus::Finished => 'gray',
                    }),

                TextColumn::make('scores')
                    ->label('Score')
                    ->getStateUsing(function (Game $record) {
                        $players = $record->players;
                        if ($players->count() < 2) {
                            return '-';
                        }
                        $player1 = $players->get(0);
                        $player2 = $players->get(1);
                        $score1 = $record->getPlayerScore($player1);
                        $score2 = $record->getPlayerScore($player2);

                        return "{$score1} - {$score2}";
                    }),

                TextColumn::make('current_turn')
                    ->label('Turn')
                    ->getStateUsing(function (Game $record) {
                        if ($record->status === GameStatus::Finished) {
                            return '';
                        }

                        return $record->currentTurnUser?->username ?? '-';
                    }),

                TextColumn::make('winner.username')
                    ->label('Winner')
                    ->badge()
                    ->color('success')
                    ->visible(fn (?Game $record) => $record && $record->status === GameStatus::Finished && $record->winner !== null),

                TextColumn::make('language')
                    ->label('Language')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('updated_at')
                    ->label('Last Move')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'finished' => 'Finished',
                    ]),
                SelectFilter::make('language')
                    ->options([
                        'nl' => 'NL',
                        'en' => 'EN',
                    ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
