<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name')->unique();
            $table->unsignedSmallInteger('level_min');
            $table->unsignedSmallInteger('level_max');
            $table->unsignedSmallInteger('tier')->default(1);
            $table->timestamps();
        });

        Schema::create('loot_tables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('monsters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('map_id');
            $table->string('name');
            $table->unsignedSmallInteger('level');
            $table->jsonb('stats')->default(DB::raw("'{}'::jsonb"));     // hp, atk, def, crit, etc.
            $table->jsonb('abilities')->default(DB::raw("'{}'::jsonb")); // efekty specjalne
            $table->unsignedBigInteger('loot_table_id')->nullable();
            $table->timestamps();

            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->foreign('loot_table_id')->references('id')->on('loot_tables')->nullOnDelete();
            $table->index(['map_id', 'level']);
        });

        Schema::create('loot_table_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loot_table_id');
            $table->string('reward_type', 16); // item|material|gold|gems
            $table->ulid('ref_ulid')->nullable(); // gdy item_template/material identyfikowany ULID-em
            $table->unsignedBigInteger('ref_numeric_id')->nullable(); // alternatywnie numeric id
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(1);
            $table->jsonb('conditions')->default(DB::raw("'{}'::jsonb")); // np. {luckMin:5}
            $table->timestamps();

            $table->foreign('loot_table_id')->references('id')->on('loot_tables')->cascadeOnDelete();
            $table->index(['loot_table_id', 'reward_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loot_table_entries');
        Schema::dropIfExists('monsters');
        Schema::dropIfExists('loot_tables');
        Schema::dropIfExists('maps');
    }
};
