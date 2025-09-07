<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure loot_tables exists (should already exist from previous migration)
        if (!Schema::hasTable('loot_tables')) {
            Schema::create('loot_tables', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Ensure loot_table_entries exists with proper structure
        if (!Schema::hasTable('loot_table_entries')) {
            Schema::create('loot_table_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('loot_table_id');
                $table->string('reward_type', 16); // gold|gems|material|item
                $table->ulid('ref_ulid')->nullable(); // item_template/material ULID
                $table->unsignedBigInteger('ref_numeric_id')->nullable(); // alternative numeric id
                $table->unsignedInteger('weight')->default(1);
                $table->unsignedInteger('min_qty')->default(1);
                $table->unsignedInteger('max_qty')->default(1);
                $table->jsonb('conditions')->default(DB::raw("'{}'::jsonb"));
                $table->timestamps();

                $table->foreign('loot_table_id')->references('id')->on('loot_tables')->cascadeOnDelete();
                $table->index(['loot_table_id', 'reward_type']);
            });
        }

        // Ensure monsters table has loot_table_id
        if (!Schema::hasColumn('monsters', 'loot_table_id')) {
            Schema::table('monsters', function (Blueprint $table) {
                $table->unsignedBigInteger('loot_table_id')->nullable()->after('abilities');
                $table->foreign('loot_table_id')->references('id')->on('loot_tables')->nullOnDelete();
            });
        }

        // Create item_ledger table for tracking item movements
        if (!Schema::hasTable('item_ledger')) {
            Schema::create('item_ledger', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->ulid('character_id');
                $table->ulid('item_instance_id');
                $table->string('action', 32); // drop|pickup|craft|upgrade|trade|consume
                $table->string('ref_type', 32)->nullable(); // encounter|trade|craft
                $table->string('ref_id')->nullable(); // encounter_id, trade_id, etc.
                $table->integer('quantity_change'); // +1, -1, etc.
                $table->string('idempotency_key')->unique();
                $table->timestamps();

                $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
                $table->index(['character_id', 'action']);
                $table->index(['ref_type', 'ref_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_ledger');

        if (Schema::hasColumn('monsters', 'loot_table_id')) {
            Schema::table('monsters', function (Blueprint $table) {
                $table->dropForeign(['loot_table_id']);
                $table->dropColumn('loot_table_id');
            });
        }
    }
};
