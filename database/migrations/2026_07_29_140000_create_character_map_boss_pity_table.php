<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_map_boss_pity', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->unsignedSmallInteger('map_id');
            $table->unsignedInteger('kills_since_boss')->default(0);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->unique(['character_id', 'map_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_map_boss_pity');
    }
};
