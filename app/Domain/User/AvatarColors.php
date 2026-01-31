<?php

namespace App\Domain\User;

final class AvatarColors
{
    /** @var array<string> Colors with good contrast for white text (WCAG AA compliant) */
    public const array COLORS = [
        '#4A90D9', // Blue
        '#9B59B6', // Purple
        '#27AE60', // Green
        '#E67E22', // Orange
        '#E74C3C', // Red
        '#1ABC9C', // Teal
        '#8E44AD', // Deep Purple
        '#2980B9', // Dark Blue
        '#C0392B', // Dark Red
        '#16A085', // Dark Teal
        '#D35400', // Burnt Orange
        '#7B68EE', // Medium Slate Blue
    ];

    public static function random(): string
    {
        return self::COLORS[array_rand(self::COLORS)];
    }
}
