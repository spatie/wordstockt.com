<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;

class RemoveAvatarAction
{
    public function execute(User $user): void
    {
        $user->clearMediaCollection('avatar');
    }
}
