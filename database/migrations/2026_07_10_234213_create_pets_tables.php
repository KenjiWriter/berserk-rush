<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->string('name');
            $table->string('rarity')->default('common'); // common, uncommon, rare, epic, legendary
            $table->json('stats')->nullable(); // {str, agi, int, vit, ...}
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedBigInteger('exp')->default(0);
            $table->boolean('is_equipped')->default(false);
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->index('character_id');
        });

        Schema::create('character_incubators', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->ulid('egg_item_instance_id')->nullable();
            $table->foreign('egg_item_instance_id')->references('id')->on('item_instances')->nullOnDelete();
            $table->string('egg_rarity')->default('common');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('hatches_at')->nullable();
            $table->boolean('is_hatched')->default(false);
            $table->timestamps();

            $table->unique('character_id'); // 1 incubator per character
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_incubators');
        Schema::dropIfExists('pets');
    }
};
