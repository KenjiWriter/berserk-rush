<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guilds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('title')->nullable();
            $table->integer('min_level')->default(1);
            $table->boolean('is_public')->default(false);
            $table->integer('level')->default(0);
            $table->unsignedBigInteger('xp')->default(0);
            $table->unsignedBigInteger('gold')->default(0);
            $table->integer('gems')->default(0);
            $table->integer('bonus_xp_level')->default(0);
            $table->integer('bonus_gold_level')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guilds');
    }
};
