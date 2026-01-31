<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    // Colors with good contrast for white text (WCAG AA compliant)
    private const array AVATAR_COLORS = [
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

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_color', 7)->nullable()->after('avatar');
        });

        // Backfill existing users with random colors
        DB::table('users')
            ->whereNull('avatar_color')
            ->get()
            ->each(function ($user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'avatar_color' => self::AVATAR_COLORS[array_rand(self::AVATAR_COLORS)],
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('avatar_color');
        });
    }
};
