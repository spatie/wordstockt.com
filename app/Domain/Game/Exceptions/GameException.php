<?php

namespace App\Domain\Game\Exceptions;

use Exception;

class GameException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('Game not found.', 404);
    }

    public static function notAPlayer(): self
    {
        return new self('You are not a player in this game.', 403);
    }

    public static function alreadyJoined(): self
    {
        return new self('You have already joined this game.');
    }

    public static function gameFull(): self
    {
        return new self('This game is already full.');
    }

    public static function gameNotPending(): self
    {
        return new self('This game is not accepting new players.');
    }

    public static function cannotPlayAgainstSelf(): self
    {
        return new self('You cannot play against yourself.');
    }

    public static function userNotFound(): self
    {
        return new self('User not found.', 404);
    }

    public static function cannotInviteSelf(): self
    {
        return new self('You cannot invite yourself to your own game.');
    }

    public static function invalidBoardTemplate(array $errors): self
    {
        return new self('Invalid board template: '.implode(', ', $errors));
    }

    public static function userAlreadyInGame(): self
    {
        return new self('This user is already in the game.');
    }

    public static function invitationAlreadyExists(): self
    {
        return new self('This user already has a pending invitation. Wait for them to respond.');
    }

    public static function invitationNotFound(): self
    {
        return new self('Invitation not found.', 404);
    }

    public static function invitationNotPending(): self
    {
        return new self('This invitation is no longer pending.');
    }

    public static function notInvitee(): self
    {
        return new self('You are not the invitee for this invitation.', 403);
    }

    public static function inviteLinkNotFound(): self
    {
        return new self('Invite link not found.', 404);
    }

    public static function inviteLinkAlreadyUsed(): self
    {
        return new self('This invite link has already been used.');
    }

    public static function notAuthorized(): self
    {
        return new self('You are not authorized to perform this action.', 403);
    }

    public static function notGameCreator(): self
    {
        return new self('Only the game creator can perform this action.', 403);
    }

    public static function cannotDeleteActiveGame(): self
    {
        return new self('Cannot delete a game that has already started.');
    }

    public static function maxPublicGamesReached(): self
    {
        return new self('You already have 10 pending public games. Please wait for someone to join or delete some games.');
    }
}
