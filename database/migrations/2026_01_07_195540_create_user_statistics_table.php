<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('user_statistics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('highest_scoring_word', 50)->nullable();
            $table->unsignedInteger('highest_scoring_word_score')->default(0);
            $table->unsignedInteger('highest_scoring_move')->default(0);
            $table->unsignedInteger('bingos_count')->default(0);
            $table->string('longest_word', 50)->nullable();
            $table->unsignedTinyInteger('longest_word_length')->default(0);
            $table->unsignedInteger('total_words_played')->default(0);
            $table->unsignedInteger('total_points_scored')->default(0);

            $table->unsignedInteger('games_lost')->default(0);
            $table->unsignedInteger('games_draw')->default(0);
            $table->unsignedInteger('highest_game_score')->default(0);
            $table->unsignedInteger('total_game_score')->default(0);
            $table->unsignedInteger('current_win_streak')->default(0);
            $table->unsignedInteger('best_win_streak')->default(0);
            $table->unsignedInteger('biggest_comeback')->default(0);
            $table->unsignedInteger('closest_victory')->nullable();

            $table->unsignedInteger('triple_word_tiles_used')->default(0);
            $table->unsignedInteger('double_word_tiles_used')->default(0);
            $table->unsignedInteger('blank_tiles_played')->default(0);

            $table->unsignedInteger('first_moves_played')->default(0);
            $table->unsignedInteger('first_move_wins')->default(0);

            $table->unsignedInteger('highest_elo_ever')->default(1200);
            $table->unsignedInteger('lowest_elo_ever')->default(1200);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_statistics');
    }
};
