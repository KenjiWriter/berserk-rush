<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('combat_skills')->update([
            'base_mana_cost' => DB::raw('base_mana_cost * 3'),
            'scaling_mana_cost' => DB::raw('scaling_mana_cost * 3'),
        ]);
    }

    public function down(): void
    {
        DB::table('combat_skills')->update([
            'base_mana_cost' => DB::raw('CAST(base_mana_cost / 3 AS UNSIGNED)'),
            'scaling_mana_cost' => DB::raw('CAST(scaling_mana_cost / 3 AS UNSIGNED)'),
        ]);
    }
};
