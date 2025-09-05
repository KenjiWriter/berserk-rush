<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('character_id');
            $table->unsignedSmallInteger('map_id');
            $table->unsignedBigInteger('monster_id');
            $table->string('state', 12)->default('ongoing'); // ongoing|win|lose
            $table->jsonb('result')->default(DB::raw("'{}'::jsonb")); // xp, gold, drops summary
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->foreign('monster_id')->references('id')->on('monsters')->cascadeOnDelete();
            $table->index(['character_id', 'state']);
        });

        Schema::create('turns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('encounter_id');
            $table->unsignedInteger('turn_no');
            $table->string('attacker', 8); // char|monster
            $table->integer('damage');
            $table->boolean('crit')->default(false);
            $table->jsonb('status')->default(DB::raw("'{}'::jsonb")); // np. buff/debuff
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->index(['encounter_id', 'turn_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turns');
        Schema::dropIfExists('encounters');
    }
};
