<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('character_equipment_set_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('character_id');
            $table->string('set_type'); // pvp, guild_war, set_1, set_2, set_3
            $table->string('slot'); // head, chest, main_hand, neck, ring, feet
            $table->ulid('item_instance_id');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('item_instance_id')->references('id')->on('item_instances')->cascadeOnDelete();
            $table->unique(['character_id', 'set_type', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_equipment_set_items');
    }
};
