<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Backfill: dla materiałów (type='material') wyprowadzamy tier mapy, z której
        // pochodzą, przez join loot_table_entries -> monsters -> maps. Loot jest czysto
        // podzielony 1:1 per mapa w seed danych (patrz MonsterLootSeeder), więc bierzemy
        // pierwszy/najniższy trafiony tier na wypadek gdyby materiał wypadał z kilku map.
        $rows = DB::table('item_templates as it')
            ->join('loot_table_entries as lte', 'lte.ref_ulid', '=', 'it.id')
            ->join('monsters as m', 'm.loot_table_id', '=', 'lte.loot_table_id')
            ->join('maps as mp', 'mp.id', '=', 'm.map_id')
            ->where('it.type', 'material')
            ->where('lte.reward_type', 'material')
            ->select('it.id as item_id', 'mp.tier as tier')
            ->distinct()
            ->get();

        $tierByItem = [];
        foreach ($rows as $row) {
            if (!isset($tierByItem[$row->item_id]) || $row->tier < $tierByItem[$row->item_id]) {
                $tierByItem[$row->item_id] = $row->tier;
            }
        }

        foreach ($tierByItem as $itemId => $tier) {
            DB::table('item_templates')->where('id', $itemId)->update(['source_map_tier' => $tier]);
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
