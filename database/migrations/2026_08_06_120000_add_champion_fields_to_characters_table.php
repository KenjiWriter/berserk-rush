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
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('champion_level')->default(0)->after('level');
            // bigInteger - próg expa (suma wymaganego expa na poziomy 1-99, x6 z krzywej)
            // liczony w miliardach, zwykły integer w Postgresie (max ~2.1mld) by się przepełnił.
            $table->bigInteger('champion_xp')->default(0)->after('champion_level');
            $table->integer('champion_points')->default(0)->after('champion_xp');
            $table->jsonb('champion_material_progress')->nullable()->after('champion_points');
            $table->timestamp('last_champion_reset_at')->nullable()->after('champion_material_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'champion_level',
                'champion_xp',
                'champion_points',
                'champion_material_progress',
                'last_champion_reset_at',
            ]);
        });
    }
};
