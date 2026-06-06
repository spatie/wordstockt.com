<?php

namespace Database\Factories;

use App\Domain\User\Models\Device;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => $this->faker->uuid(),
            'platform' => $this->faker->randomElement(['ios', 'android']),
            'os_version' => $this->faker->numerify('##.#'),
            'model' => $this->faker->randomElement(['iPhone 15 Pro', 'Pixel 8', 'iPhone 13']),
            'app_version' => '1.7.0',
            'last_seen_at' => now(),
        ];
    }
}
