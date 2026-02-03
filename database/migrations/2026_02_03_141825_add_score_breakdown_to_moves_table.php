<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('moves', function (Blueprint $table) {
            $table->json('score_breakdown')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('moves', function (Blueprint $table) {
            $table->dropColumn('score_breakdown');
        });
    }
};
