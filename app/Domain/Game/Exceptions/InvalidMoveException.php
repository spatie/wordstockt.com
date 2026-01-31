<?php

namespace App\Domain\Game\Exceptions;

use Exception;

class InvalidMoveException extends Exception
{
    public readonly int $statusCode;

    public function __construct(string $message)
    {
        parent::__construct($message);
        $this->statusCode = 422;
    }

    public static function noTilesPlaced(): self
    {
        return new self('You must place at least one tile.');
    }

    public static function outOfBounds(): self
    {
        return new self('Tile placement is out of bounds.');
    }

    public static function positionOccupied(): self
    {
        return new self('A tile already exists at that position.');
    }

    public static function notInLine(): self
    {
        return new self('All tiles must be placed in a single row or column.');
    }

    public static function hasGaps(): self
    {
        return new self('There cannot be gaps between tiles.');
    }

    public static function mustCoverCenter(): self
    {
        return new self('The first move must cover the center square.');
    }

    public static function notConnected(): self
    {
        return new self('Tiles must connect to existing tiles on the board.');
    }

    public static function invalidWord(string $word): self
    {
        return new self("'{$word}' is not a valid word.");
    }

    public static function invalidWords(array $words): self
    {
        $wordList = implode("', '", $words);

        return new self("The following words are not valid: '{$wordList}'.");
    }

    public static function notYourTurn(): self
    {
        return new self("It's not your turn.");
    }

    public static function gameNotActive(): self
    {
        return new self('This game is not active.');
    }

    public static function tilesNotInRack(): self
    {
        return new self('You do not have those tiles in your rack.');
    }

    public static function notEnoughTilesForSwap(): self
    {
        return new self('There are not enough tiles in the bag to swap.');
    }
}
