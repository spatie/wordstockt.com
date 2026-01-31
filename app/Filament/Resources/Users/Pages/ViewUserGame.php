<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\Page;

class ViewUserGame extends Page
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.pages.view-user-game';

    public User $record;

    public ?Game $game = null;

    public function mount(string $record, string $game): void
    {
        $this->record = User::findOrFail($record);
        $this->game = $this->record->games()
            ->with(['players', 'gamePlayers.user', 'moves.user', 'currentTurnUser', 'winner', 'latestMove'])
            ->findOrFail($game);
    }

    public function getTitle(): string
    {
        $opponent = $this->game->getOpponent($this->record);

        return "Game vs {$opponent?->username}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            UserResource::getUrl('index') => 'Users',
            UserResource::getUrl('view', ['record' => $this->record]) => $this->record->username,
            '#' => 'Game',
        ];
    }

    protected function getViewData(): array
    {
        $opponent = $this->game->getOpponent($this->record);
        $userGamePlayer = $this->game->getGamePlayer($this->record);
        $opponentGamePlayer = $opponent ? $this->game->getGamePlayer($opponent) : null;

        $lastMoveTiles = $this->game->latestMove?->tiles ?? [];

        return [
            'game' => $this->game,
            'user' => $this->record,
            'opponent' => $opponent,
            'userGamePlayer' => $userGamePlayer,
            'opponentGamePlayer' => $opponentGamePlayer,
            'lastMoveTiles' => $lastMoveTiles,
            'moves' => $this->game->moves()
                ->with('user')
                ->latest()
                ->get(),
        ];
    }
}
