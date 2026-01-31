<?php

namespace App\Domain\User\Exceptions;

use Exception;

class FriendException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 400,
    ) {
        parent::__construct($message);
    }

    public static function alreadyFriend(): self
    {
        return new self('Already in friends list.', 400);
    }

    public static function cannotAddSelf(): self
    {
        return new self('Cannot add yourself as a friend.', 400);
    }

    public static function userNotFound(): self
    {
        return new self('User not found.', 404);
    }

    public static function friendNotFound(): self
    {
        return new self('Friend not found.', 404);
    }
}
