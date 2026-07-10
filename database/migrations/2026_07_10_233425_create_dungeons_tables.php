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
        Schema::create('dungeons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('min_level')->default(1);
            $table->ulid('entry_item_template_id')->nullable();
            $table->foreign('entry_item_template_id')->references('id')->on('item_templates')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dungeon_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('stage_order');
            $table->foreignId('monster_id')->constrained('monsters')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('character_dungeon_runs', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('current_stage')->default(1);
            $table->bigInteger('current_hp');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_failed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_dungeon_runs');
        Schema::dropIfExists('dungeon_stages');
        Schema::dropIfExists('dungeons');
    }
};
