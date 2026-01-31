<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('dictionaries', function (Blueprint $table) {
            $table->foreignId('first_played_by_user_id')
                ->nullable()
                ->after('last_played_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('user_statistics', function (Blueprint $table) {
            $table->unsignedInteger('unique_words_played')->default(0)->after('total_words_played');
        });
    }

    public function down(): void
    {
        Schema::table('dictionaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('first_played_by_user_id');
        });

        Schema::table('user_statistics', function (Blueprint $table) {
            $table->dropColumn('unique_words_played');
        });
    }
};
