@props([
    'boardState' => [],
    'boardTemplate' => null,
    'lastMoveTiles' => [],
])

@php
    use App\Domain\Game\Support\Board;

    $board = new Board();
    $lastMoveCoordinates = collect($lastMoveTiles)->mapWithKeys(function($tile) {
        return ["{$tile['x']},{$tile['y']}" => true];
    });
@endphp

{{-- Board with border matching the game --}}
<div style="display: inline-block; background-color: #1B2838; border-radius: 16px; padding: 8px; border: 2px solid #4A90D9; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.35);">
    <div style="display: grid; grid-template-columns: repeat(15, 40px); gap: 1px; background-color: #0D1520;">
        @for ($y = 0; $y < Board::BOARD_SIZE; $y++)
            @for ($x = 0; $x < Board::BOARD_SIZE; $x++)
                @php
                    $tile = $boardState[$y][$x] ?? null;
                    $squareType = $board->getSquareType($x, $y, $boardTemplate);
                    $isCenter = $board->isCenter($x, $y);
                    $isLastMove = $lastMoveCoordinates->has("{$x},{$y}");
                @endphp

                <x-game.board-cell
                    :tile="$tile"
                    :squareType="$squareType?->value"
                    :isCenter="$isCenter"
                    :isLastMove="$isLastMove"
                />
            @endfor
        @endfor
    </div>
</div>
