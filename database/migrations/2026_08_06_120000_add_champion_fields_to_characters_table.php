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
            // Brak osobnej kolumny na expa championa - postać na 99 poziomie dalej
            // używa ISTNIEJĄCEJ kolumny `xp` (już unsignedBigInteger, wystarczająco
            // pojemnej), tylko z wyższym limitem (suma expa 1-99, ~2.6mld zamiast
            // ~138mln) - patrz LevelUpService::getMaxLevelXpCap()/ChampionService::xpTarget().
            $table->integer('champion_points')->default(0)->after('champion_level');
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
                'champion_points',
                'champion_material_progress',
                'last_champion_reset_at',
            ]);
        });
    }
};
