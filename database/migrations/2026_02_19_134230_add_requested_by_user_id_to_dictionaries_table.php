<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('dictionaries', function (Blueprint $table) {
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dictionaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by_user_id');
        });
    }
};
