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
        Schema::create('world_boss_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('map_id');
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->foreignId('monster_id')->constrained()->onDelete('cascade');
            $table->bigInteger('total_hp');
            $table->bigInteger('current_hp');
            $table->boolean('is_defeated')->default(false);
            $table->timestamps();
        });

        Schema::create('world_boss_damage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_boss_instance_id')->constrained()->onDelete('cascade');
            $table->ulid('character_id');
            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->bigInteger('damage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_boss_damage_logs');
        Schema::dropIfExists('world_boss_instances');
    }
};
