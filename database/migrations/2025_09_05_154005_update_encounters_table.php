<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->boolean('player_first')->default(true);

            // Combat data (JSONB for flexibility)
            $table->jsonb('turns')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('combat_data')->default(DB::raw("'{}'::jsonb"));
            $table->boolean('rewards_applied')->default(false);

            $table->index(['character_id', 'created_at']);
            $table->index(['state', 'created_at']);
            $table->index('rewards_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
