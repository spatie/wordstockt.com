<?php

namespace App\Domain\User\Actions\Auth;

use App\Domain\User\AvatarColors;
use App\Domain\User\Models\User;

class CreateGuestUserAction
{
    private const array ADJECTIVES = [
        'Swift', 'Clever', 'Bold', 'Quick', 'Bright', 'Lucky', 'Brave', 'Sharp',
        'Cool', 'Wise', 'Wild', 'Free', 'Happy', 'Mighty', 'Noble', 'Keen',
    ];

    private const array NOUNS = [
        'Player', 'Owl', 'Fox', 'Bear', 'Wolf', 'Hawk', 'Tiger', 'Lion',
        'Eagle', 'Falcon', 'Raven', 'Dragon', 'Phoenix', 'Knight', 'Wizard', 'Star',
    ];

    public function execute(): User
    {
        $username = $this->generateUniqueUsername();

        return User::create([
            'username' => $username,
            'email' => null,
            'password' => null,
            'is_guest' => true,
            'avatar_color' => AvatarColors::random(),
        ]);
    }

    private function generateUniqueUsername(): string
    {
        do {
            $adjective = self::ADJECTIVES[array_rand(self::ADJECTIVES)];
            $noun = self::NOUNS[array_rand(self::NOUNS)];
            $number = random_int(100, 999);
            $username = "{$adjective}{$noun}{$number}";
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
