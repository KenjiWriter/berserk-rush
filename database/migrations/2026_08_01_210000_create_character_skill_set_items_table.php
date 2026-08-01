<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('character_skill_set_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('character_id');
            $table->string('set_type'); // pvp, guild_war, set_1, set_2, set_3
            $table->unsignedInteger('equip_slot'); // 1, 2, 3
            $table->ulid('combat_skill_id');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('combat_skill_id')->references('id')->on('combat_skills')->cascadeOnDelete();
            $table->unique(['character_id', 'set_type', 'equip_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_skill_set_items');
    }
};
