<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('user_statistics', function (Blueprint $table): void {
            $table->unsignedInteger('games_won')->default(0)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_statistics', function (Blueprint $table): void {
            $table->dropColumn('games_won');
        });
    }
};
