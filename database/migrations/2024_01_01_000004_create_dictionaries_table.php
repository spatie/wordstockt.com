<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('dictionaries', function (Blueprint $table): void {
            $table->id();
            $table->string('language', 5);
            $table->string('word', 50);

            $table->unique(['language', 'word']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dictionaries');
    }
};
