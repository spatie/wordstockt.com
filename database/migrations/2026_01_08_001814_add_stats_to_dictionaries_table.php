<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('dictionaries', function (Blueprint $table): void {
            $table->unsignedInteger('times_played')->default(0);
            $table->timestamp('first_played_at')->nullable();
            $table->timestamp('last_played_at')->nullable();
            $table->text('definition')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dictionaries', function (Blueprint $table): void {
            $table->dropColumn(['times_played', 'first_played_at', 'last_played_at', 'definition']);
        });
    }
};
