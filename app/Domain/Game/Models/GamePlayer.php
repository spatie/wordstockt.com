<?php

namespace App\Domain\Game\Models;

use App\Domain\User\Models\User;
use Database\Factories\GamePlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $user
 * @property array<int, array{letter: string, points: int, is_blank?: bool}>|null $rack_tiles
 */
class GamePlayer extends Model
{
    use HasFactory;

    protected static function newFactory(): GamePlayerFactory
    {
        return GamePlayerFactory::new();
    }

    protected function casts(): array
    {
        return [
            'rack_tiles' => 'array',
            'score' => 'integer',
            'turn_order' => 'integer',
            'has_free_swap' => 'boolean',
            'has_received_blank' => 'boolean',
            'received_empty_rack_bonus' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addScore(int $points): void
    {
        $this->increment('score', $points);
    }

    public function useFreeSwap(): void
    {
        $this->update(['has_free_swap' => false]);
    }

    public function setRackTiles(array $tiles): void
    {
        $this->update(['rack_tiles' => $tiles]);
    }

    public function getRackTileCount(): int
    {
        return count($this->rack_tiles ?? []);
    }

    /**
     * @param  array<int, array{letter: string, points: int, is_blank?: bool}>  $tilesToRemove
     * @return array<int, array{letter: string, points: int, is_blank?: bool}>
     */
    public function removeTilesFromRack(array $tilesToRemove): array
    {
        $remaining = collect($this->rack_tiles ?? []);

        collect($tilesToRemove)->each(function (array $tile) use (&$remaining): void {
            $index = $remaining->search(fn (array $rackTile): bool => $this->tileMatchesRackTile($tile, $rackTile));

            if ($index !== false) {
                $remaining->forget($index);
            }
        });

        return $remaining->values()->all();
    }

    private function tileMatchesRackTile(array $tile, array $rackTile): bool
    {
        if ($tile['is_blank'] ?? false) {
            return ($rackTile['is_blank'] ?? false) || $rackTile['letter'] === '*';
        }

        return $rackTile['letter'] === $tile['letter'] && $rackTile['points'] === $tile['points'];
    }
}
