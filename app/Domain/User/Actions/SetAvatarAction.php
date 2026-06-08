<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;

class SetAvatarAction
{
    public function execute(User $user, UploadedFile $avatar): void
    {
        $user->addMedia($avatar)->toMediaCollection('avatar');
    }
}
