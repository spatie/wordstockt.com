<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table): void {
            $table->boolean('has_free_swap')->default(true)->after('turn_order');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table): void {
            $table->dropColumn('has_free_swap');
        });
    }
};
