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
        Schema::table('combat_skills', function (Blueprint $table) {
            $table->integer('base_mana_cost')->default(0)->after('base_value');
            $table->integer('scaling_mana_cost')->default(0)->after('base_mana_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combat_skills', function (Blueprint $table) {
            $table->dropColumn(['base_mana_cost', 'scaling_mana_cost']);
        });
    }
};
