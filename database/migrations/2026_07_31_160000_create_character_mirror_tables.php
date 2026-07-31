<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('character_mirror_sessions');
        Schema::dropIfExists('character_map_mirror_stats');

        Schema::create('character_map_mirror_stats', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->unsignedSmallInteger('map_id');
            $table->unsignedBigInteger('max_exp_per_minute')->default(0);
            $table->unsignedBigInteger('max_gold_per_minute')->default(0);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->unique(['character_id', 'map_id']);
        });

        Schema::create('character_mirror_sessions', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->unsignedSmallInteger('map_id');
            $table->unsignedInteger('duration_hours');
            $table->unsignedBigInteger('exp_per_minute')->default(0);
            $table->unsignedBigInteger('gold_per_minute')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active'); // active, claimed, cancelled
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->index(['character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_mirror_sessions');
        Schema::dropIfExists('character_map_mirror_stats');
    }
};
