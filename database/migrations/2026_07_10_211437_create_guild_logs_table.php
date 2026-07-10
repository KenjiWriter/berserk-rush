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
        Schema::create('guild_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('guild_id')->constrained('guilds')->cascadeOnDelete();
            $table->foreignUlid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('action'); // 'donate_exp', 'donate_gold', 'donate_gems'
            $table->bigInteger('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_logs');
    }
};
