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
        Schema::table('world_boss_instances', function (Blueprint $table) {
            $table->dropColumn('is_defeated');
            $table->string('level_bracket')->nullable()->after('monster_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('world_boss_instances', function (Blueprint $table) {
            $table->boolean('is_defeated')->default(false);
            $table->dropColumn('level_bracket');
        });
    }
};
