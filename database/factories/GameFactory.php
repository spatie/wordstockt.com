<?php

namespace Database\Factories;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Board;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'ulid' => strtolower(Str::ulid()->toString()),
            'language' => fake()->randomElement(['en', 'nl']),
            'board_state' => (new Board)->createEmptyBoard(),
            'tile_bag' => [],
            'status' => GameStatus::Active,
            'current_turn_user_id' => null,
            'winner_id' => null,
            'consecutive_passes' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => GameStatus::Pending]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => GameStatus::Active]);
    }

    public function finished(): static
    {
        return $this->state(fn (): array => ['status' => GameStatus::Finished]);
    }
}
