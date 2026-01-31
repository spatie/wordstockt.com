<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('head_to_head_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opponent_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->unsignedInteger('total_score_for')->default(0);
            $table->unsignedInteger('total_score_against')->default(0);

            $table->string('best_word', 50)->nullable();
            $table->unsignedInteger('best_word_score')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'opponent_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('head_to_head_stats');
    }
};
