<?php

namespace App\Filament\Widgets;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    #[\Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Registered users', User::count()),
            Stat::make('Games played (past month)', Game::where('created_at', '>=', now()->subMonth())->count()),
        ];
    }
}
