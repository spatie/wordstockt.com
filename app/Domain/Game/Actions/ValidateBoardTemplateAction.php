<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Data\BoardTemplateValidationResult;
use App\Domain\Game\Enums\SquareType;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\BoardTemplate;
use Illuminate\Support\Collection;

class ValidateBoardTemplateAction
{
    public function execute(array $template): BoardTemplateValidationResult
    {
        $errors = collect()
            ->merge($this->validateDimensions($template))
            ->merge($this->validateMultiplierValues($template))
            ->merge($this->validateCenterCell($template))
            ->merge($this->validateMultiplierLimits($template));

        return new BoardTemplateValidationResult($errors->isEmpty(), $errors->all());
    }

    private function validateDimensions(array $template): Collection
    {
        if (count($template) !== Board::BOARD_SIZE) {
            return collect(['Board must have exactly '.Board::BOARD_SIZE.' rows']);
        }

        return collect($template)
            ->filter(fn ($row): bool => ! is_array($row) || count($row) !== Board::BOARD_SIZE)
            ->map(fn ($_, $y): string => "Row {$y} must have exactly ".Board::BOARD_SIZE.' cells')
            ->values();
    }

    private function validateMultiplierValues(array $template): Collection
    {
        $validValues = [...array_column(SquareType::cases(), 'value'), null];

        return collect($template)
            ->flatMap(fn ($row, $y) => collect($row)
                ->filter(fn ($cell): bool => ! in_array($cell, $validValues, true))
                ->map(fn ($cell, $x): string => "Invalid multiplier '{$cell}' at position ({$x}, {$y})")
            )
            ->values();
    }

    private function validateCenterCell(array $template): Collection
    {
        $center = Board::CENTER;
        $centerValue = $template[$center][$center] ?? null;

        if ($centerValue !== SquareType::Star->value) {
            return collect(['Center cell must be STAR']);
        }

        return collect();
    }

    private function validateMultiplierLimits(array $template): Collection
    {
        $counts = $this->countMultipliers($template);
        $limits = BoardTemplate::MULTIPLIER_LIMITS;

        return collect($limits)
            ->filter(fn ($limit, $type): bool => ($counts[$type] ?? 0) > $limit)
            ->map(fn ($limit, $type): string => "{$type} count exceeds limit of {$limit} (found ".($counts[$type] ?? 0).')')
            ->values();
    }

    private function countMultipliers(array $template): array
    {
        return collect($template)
            ->flatten()
            ->filter()
            ->filter(fn ($v): bool => $v !== SquareType::Star->value)
            ->countBy()
            ->all();
    }
}
