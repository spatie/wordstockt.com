<?php

namespace Database\Factories;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GamePlayer>
 */
class GamePlayerFactory extends Factory
{
    protected $model = GamePlayer::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'rack_tiles' => [],
            'score' => 0,
            'turn_order' => 1,
        ];
    }

    public function withRack(array $tiles): static
    {
        return $this->state(fn (): array => ['rack_tiles' => $tiles]);
    }

    public function withScore(int $score): static
    {
        return $this->state(fn (): array => ['score' => $score]);
    }
}
