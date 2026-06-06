<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table): void {
            $table->unsignedTinyInteger('consecutive_passes')->default(0)->after('turn_order');
            $table->timestamp('left_at')->nullable()->after('consecutive_passes');
            $table->string('left_reason')->nullable()->after('left_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table): void {
            $table->dropColumn(['consecutive_passes', 'left_at', 'left_reason']);
        });
    }
};
