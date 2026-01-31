<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('user_word_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dictionary_id')->constrained('dictionaries')->cascadeOnDelete();
            $table->unsignedInteger('times_played')->default(1);
            $table->timestamp('first_played_at');

            $table->unique(['user_id', 'dictionary_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_word_plays');
    }
};
