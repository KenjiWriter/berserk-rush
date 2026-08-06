<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialMapTierSeeder extends Seeder
{
    /**
     * Wyprowadza `item_templates.source_map_tier` dla materiałów (type='material')
     * z tieru mapy, na której faktycznie wypadają (join loot_table_entries ->
     * monsters -> maps). Używane przez system Mistrzostwa (patrz
     * App\Application\Mastery\ChampionService::buildRandomMaterialRequirements())
     * do ważenia losowanych ulepszaczy wg tieru. Idempotentne - bezpieczne do
     * wielokrotnego uruchomienia po każdym reseedzie tabel lootu.
     */
    public function run(): void
    {
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
}
