<?php

use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Rules\Turn\TilesInRackRule;
use App\Domain\Game\Support\Tile;
use App\Domain\Game\Support\TileBag;
use App\Domain\Game\Support\TileDistributions\DutchDistribution;

describe('Blank Tile Behavior', function (): void {
    describe('Tile Distribution', function (): void {
        it('does not include blank tiles in tile pool (they are given separately)', function (): void {
            $distribution = new DutchDistribution;
            $tiles = $distribution->tiles();

            $blankTiles = collect($tiles)->filter(fn ($tile): bool => $tile->isBlank);

            expect($blankTiles)->toHaveCount(0);
        });

        it('creates blank tiles via Tile::blank() factory method', function (): void {
            $blankTile = Tile::blank();
            $array = $blankTile->toArray();

            expect($array['letter'])->toBe('*');
            expect($array['points'])->toBe(0);
            expect($array['is_blank'])->toBeTrue();
        });
    });

    describe('Tile Bag', function (): void {
        it('draws blank tiles with asterisk letter from bag', function (): void {
            // Create a tile bag with only blank tiles for predictable testing
            $tileBag = TileBag::fromArray([
                ['letter' => '*', 'points' => 0, 'is_blank' => true],
            ]);

            $drawn = $tileBag->drawAsArrays(1);

            expect($drawn[0]['letter'])->toBe('*');
            expect($drawn[0]['points'])->toBe(0);
            expect($drawn[0]['is_blank'])->toBeTrue();
        });

        it('swaps one tile for a blank', function (): void {
            $tileBag = TileBag::fromArray([]);
            $tiles = [
                new Tile('A', 1),
                new Tile('B', 3),
                new Tile('C', 3),
            ];

            $result = $tileBag->swapOneForBlank($tiles);

            expect($result)->toHaveCount(3);
            expect($tileBag->count())->toBe(1);

            $blankCount = collect($result)->filter(fn ($tile) => $tile->isBlank)->count();
            expect($blankCount)->toBe(1);
        });

        it('returns swapped tile to the bag when swapping for blank', function (): void {
            $tileBag = TileBag::fromArray([]);
            $tiles = [
                new Tile('A', 1),
                new Tile('B', 3),
            ];

            $tileBag->swapOneForBlank($tiles);

            expect($tileBag->count())->toBe(1);

            $returnedTile = $tileBag->draw(1)[0];
            expect($returnedTile->letter)->toBe('B');
        });

        it('converts tiles to array format', function (): void {
            $tiles = [
                new Tile('A', 1),
                new Tile('B', 3),
            ];

            $arrays = TileBag::tilesToArray($tiles);

            expect($arrays)->toHaveCount(2);
            expect($arrays[0])->toBe(['letter' => 'A', 'points' => 1, 'is_blank' => false]);
            expect($arrays[1])->toBe(['letter' => 'B', 'points' => 3, 'is_blank' => false]);
        });
    });

    describe('TilesInRackRule', function (): void {
        beforeEach(function (): void {
            $this->rule = new TilesInRackRule;
        });

        it('validates blank tile with asterisk letter in rack', function (): void {
            $game = createGameWithPlayers();
            $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();

            // Blank tiles in rack have letter '*' when drawn from tile bag
            $gamePlayer->update([
                'rack_tiles' => [
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                ],
            ]);

            $board = createEmptyBoard();
            // When playing a blank, the player specifies which letter it represents
            $move = createMove([
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 0, 'is_blank' => true],
            ]);

            $result = $this->rule->validate($game, $move, $board);

            expect($result->passed)->toBeTrue();
        });

        it('fails when no blank in rack but trying to play one', function (): void {
            $game = createGameWithPlayers();
            $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
            $gamePlayer->update([
                'rack_tiles' => [
                    ['letter' => 'A', 'points' => 1],
                    ['letter' => 'B', 'points' => 3],
                ],
            ]);

            $board = createEmptyBoard();
            $move = createMove([
                ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 0, 'is_blank' => true],
            ]);

            $result = $this->rule->validate($game, $move, $board);

            expect($result->passed)->toBeFalse();
        });

        it('handles playing two blanks in one move', function (): void {
            $game = createGameWithPlayers();
            $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
            $gamePlayer->update([
                'rack_tiles' => [
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                    ['letter' => 'T', 'points' => 2],
                ],
            ]);

            $board = createEmptyBoard();
            $move = createMove([
                ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 0, 'is_blank' => true],
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 0, 'is_blank' => true],
                ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 2, 'is_blank' => false],
            ]);

            $result = $this->rule->validate($game, $move, $board);

            expect($result->passed)->toBeTrue();
        });

        it('fails when trying to play more blanks than in rack', function (): void {
            $game = createGameWithPlayers();
            $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
            $gamePlayer->update([
                'rack_tiles' => [
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                    ['letter' => 'T', 'points' => 2],
                ],
            ]);

            $board = createEmptyBoard();
            // Try to play two blanks when we only have one
            $move = createMove([
                ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 0, 'is_blank' => true],
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 0, 'is_blank' => true],
            ]);

            $result = $this->rule->validate($game, $move, $board);

            expect($result->passed)->toBeFalse();
        });
    });

    describe('GamePlayer::removeTilesFromRack', function (): void {
        it('removes blank tile from rack when played', function (): void {
            $gamePlayer = GamePlayer::factory()->create([
                'rack_tiles' => [
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                    ['letter' => 'A', 'points' => 1],
                    ['letter' => 'B', 'points' => 3],
                ],
            ]);

            // When playing a blank, it's specified with is_blank: true and the chosen letter
            $tilesToRemove = [
                ['letter' => 'X', 'points' => 0, 'is_blank' => true],
            ];

            $remainingRack = $gamePlayer->removeTilesFromRack($tilesToRemove);

            expect($remainingRack)->toHaveCount(2);
            expect(collect($remainingRack)->pluck('letter')->all())->toBe(['A', 'B']);
        });

        it('removes regular tile from rack', function (): void {
            $gamePlayer = GamePlayer::factory()->create([
                'rack_tiles' => [
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                    ['letter' => 'A', 'points' => 1],
                    ['letter' => 'B', 'points' => 3],
                ],
            ]);

            $tilesToRemove = [
                ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ];

            $remainingRack = $gamePlayer->removeTilesFromRack($tilesToRemove);

            expect($remainingRack)->toHaveCount(2);
            // Blank and B should remain
            $letters = collect($remainingRack)->pluck('letter')->all();
            expect($letters)->toContain('*');
            expect($letters)->toContain('B');
        });

        it('removes mixed blank and regular tiles', function (): void {
            $gamePlayer = GamePlayer::factory()->create([
                'rack_tiles' => [
                    ['letter' => '*', 'points' => 0, 'is_blank' => true],
                    ['letter' => 'A', 'points' => 1],
                    ['letter' => 'T', 'points' => 2],
                ],
            ]);

            $tilesToRemove = [
                ['letter' => 'C', 'points' => 0, 'is_blank' => true], // blank played as C
                ['letter' => 'A', 'points' => 1, 'is_blank' => false],
                ['letter' => 'T', 'points' => 2, 'is_blank' => false],
            ];

            $remainingRack = $gamePlayer->removeTilesFromRack($tilesToRemove);

            expect($remainingRack)->toHaveCount(0);
        });
    });
});
