<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Filament\Resources\Games\GameResource;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GamesRelationManager extends RelationManager
{
    protected static string $relationship = 'games';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ulid')
            ->recordUrl(fn (Game $record): string => GameResource::getUrl('view', ['record' => $record->ulid]))
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['players', 'gamePlayers', 'currentTurnUser', 'winner', 'latestMove'])
                ->latest('games.updated_at')
            )
            ->columns([
                TextColumn::make('opponent')
                    ->label('Opponent')
                    ->getStateUsing(function (Game $record) {
                        $opponent = $record->getOpponent($this->getOwnerRecord());

                        return $opponent?->username ?? 'Waiting for opponent...';
                    })
                    ->searchable(false),

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
                        $user = $this->getOwnerRecord();
                        $opponent = $record->getOpponent($user);
                        $userScore = $record->getPlayerScore($user);
                        $opponentScore = $opponent ? $record->getPlayerScore($opponent) : 0;

                        return "{$userScore} - {$opponentScore}";
                    }),

                TextColumn::make('turn')
                    ->label('Turn')
                    ->icon(fn (Game $record): ?string => $record->isCurrentTurn($this->getOwnerRecord())
                        ? 'heroicon-o-arrow-right'
                        : 'heroicon-o-arrow-left'
                    )
                    ->iconColor(fn (Game $record): string => $record->isCurrentTurn($this->getOwnerRecord())
                        ? 'success'
                        : 'gray'
                    )
                    ->getStateUsing(function (Game $record) {
                        if ($record->status === GameStatus::Finished) {
                            return '';
                        }

                        return $record->isCurrentTurn($this->getOwnerRecord()) ? 'Your turn' : 'Opponent\'s turn';
                    }),

                TextColumn::make('winner')
                    ->label('Winner')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function (Game $record) {
                        if (! $record->winner) {
                            return null;
                        }

                        if ($record->isWinner($this->getOwnerRecord())) {
                            return 'You won!';
                        }

                        return $record->winner->username.' won';
                    })
                    ->visible(fn (?Game $record) => $record && $record->status === GameStatus::Finished && $record->winner !== null),

                TextColumn::make('updated_at')
                    ->label('Last Move')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Game $record): string => GameResource::getUrl('view', ['record' => $record->ulid])),
            ])
            ->toolbarActions([]);
    }
}
