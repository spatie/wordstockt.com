<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Achievement\Data\UnlockedAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UnlockedAchievement
 */
class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->achievement->id(),
            'name' => $this->achievement->definition()->name,
            'description' => $this->achievement->definition()->description,
            'icon' => $this->achievement->definition()->icon,
            'category' => $this->achievement->definition()->category,
            'context' => $this->context->toArray(),
        ];
    }
}
