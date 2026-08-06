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
        Schema::table('item_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('source_map_tier')->nullable()->after('egg_tier');
        });

        // Backfill (jednorazowy, na środowiskach gdzie loot już jest zaseedowany) -
        // logika w Database\Seeders\MaterialMapTierSeeder, żeby móc ją też odpalić
        // ponownie po pełnym reseedzie (patrz DatabaseSeeder) lub w testach, gdzie
        // migracje idą PRZED seederami na pustej bazie.
        if (Schema::hasTable('loot_table_entries') && Schema::hasTable('monsters') && Schema::hasTable('maps')) {
            (new \Database\Seeders\MaterialMapTierSeeder())->run();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->dropColumn('source_map_tier');
        });
    }
};
