<?php

namespace Database\Factories;

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Move>
 */
class MoveFactory extends Factory
{
    protected $model = Move::class;

    public function definition(): array
    {
        return [
            'ulid' => strtolower(Str::ulid()->toString()),
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'type' => MoveType::Play,
            'tiles' => [],
            'words' => [],
            'score' => 0,
        ];
    }

    public function play(): static
    {
        return $this->state(fn (): array => [
            'type' => MoveType::Play,
        ]);
    }

    public function pass(): static
    {
        return $this->state(fn (): array => [
            'type' => MoveType::Pass,
            'tiles' => null,
            'words' => null,
            'score' => 0,
        ]);
    }

    public function swap(): static
    {
        return $this->state(fn (): array => [
            'type' => MoveType::Swap,
            'tiles' => null,
            'words' => null,
            'score' => 0,
        ]);
    }

    public function resign(): static
    {
        return $this->state(fn (): array => [
            'type' => MoveType::Resign,
            'tiles' => null,
            'words' => null,
            'score' => 0,
        ]);
    }
}
