<?php

use App\Domain\Game\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->timestamp('turn_expires_at')->nullable()->after('consecutive_passes');
            $table->unsignedTinyInteger('last_turn_reminder_sent')->nullable()->after('turn_expires_at');
            $table->index(['status', 'turn_expires_at', 'current_turn_user_id'], 'games_turn_timer_index');
        });

        Game::query()
            ->where('status', 'active')
            ->whereNotNull('current_turn_user_id')
            ->update([
                'turn_expires_at' => now()->addHours(Game::turnTimeoutHours()),
            ]);
    }
};
