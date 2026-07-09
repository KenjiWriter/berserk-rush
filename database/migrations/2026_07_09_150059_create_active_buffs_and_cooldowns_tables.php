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
        Schema::create('active_buffs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('character_id');
            $table->string('name');
            $table->json('effects'); // np. {"str_bonus": 10, "hp_bonus": 50} - zsumowane z bazą
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['character_id', 'expires_at']);
        });

        Schema::create('character_cooldowns', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->string('cooldown_key'); // np. 'witch_exp_potion'
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->unique(['character_id', 'cooldown_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_buffs_and_cooldowns_tables');
    }
};
