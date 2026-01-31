<?php

namespace App\Domain\User\Actions;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\EloHistory;
use App\Domain\User\Models\Friend;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\PushToken;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;

class DeleteUserAction
{
    public function execute(User $user): void
    {
        PushToken::where('user_id', $user->id)->delete();

        $user->tokens()->delete();

        Friend::where('user_id', $user->id)
            ->orWhere('friend_id', $user->id)
            ->delete();

        GameInvitation::where('inviter_id', $user->id)
            ->orWhere('invitee_id', $user->id)
            ->delete();

        EloHistory::where('user_id', $user->id)->delete();

        HeadToHeadStats::where('user_id', $user->id)
            ->orWhere('opponent_id', $user->id)
            ->delete();

        UserStatistics::where('user_id', $user->id)->delete();

        Move::where('user_id', $user->id)->delete();

        Game::where('winner_id', $user->id)->update(['winner_id' => null]);

        GamePlayer::where('user_id', $user->id)->delete();

        $user->delete();
    }
}
