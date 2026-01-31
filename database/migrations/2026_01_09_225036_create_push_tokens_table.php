<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('device_name')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('expo_push_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');

        Schema::table('users', function (Blueprint $table): void {
            $table->string('expo_push_token')->nullable()->after('avatar');
        });
    }
};
