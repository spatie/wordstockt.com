<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('dictionaries', function (Blueprint $table) {
            $table->boolean('is_valid')->default(true);
            $table->timestamp('requested_to_mark_as_invalid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dictionaries', function (Blueprint $table) {
            $table->dropColumn(['is_valid', 'requested_to_mark_as_invalid_at']);
        });
    }
};
