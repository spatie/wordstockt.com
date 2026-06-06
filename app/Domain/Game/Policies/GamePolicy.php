<?php

namespace App\Domain\Game\Policies;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class GamePolicy
{
    public function view(User $user, Game $game): bool
    {
        // Admins can view all games
        if ($user->is_admin) {
            return true;
        }

        return $game->canBeWatchedBy($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function join(User $user, Game $game): bool
    {
        return $game->canBeJoinedBy($user);
    }

    public function invite(User $user, Game $game): bool
    {
        return $game->canBeInvitedToBy($user);
    }

    public function play(User $user, Game $game): bool
    {
        return $game->hasPlayer($user);
    }

    public function resign(User $user, Game $game): bool
    {
        return $game->hasPlayer($user);
    }
}
