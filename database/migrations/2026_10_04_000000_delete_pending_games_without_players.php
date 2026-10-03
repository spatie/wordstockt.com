<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /*
     * Deleting an account used to leave the user's pending games behind
     * without any players. Related rows are removed by foreign key cascades.
     */
    public function up(): void
    {
        DB::table('games')
            ->where('status', 'pending')
            ->whereNotExists(fn (Builder $query) => $query
                ->select(DB::raw(1))
                ->from('game_players')
                ->whereColumn('game_players.game_id', 'games.id'))
            ->delete();
    }
};
