<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zastępuje 3 stare szablony jajek sprzed reworku tierów (egg-common/egg-rare/
 * egg-epic, bez `egg_tier`) nowymi odpowiednikami T1/T2/T3 (egg-t1/egg-t2/
 * egg-t3) - mapowanie 1:1 wg identycznego `level_requirement` (1/10/20 po
 * obu stronach). Gracze, którzy mają stare jajko w plecaku, dostają
 * automatycznie działający odpowiednik zamiast utkniętego przedmiotu bez
 * przypisanego tieru (błąd "To jajko nie ma przypisanego tieru chowańca.").
 */
return new class extends Migration
{
    private const MAPPING = [
        'egg-common' => 'egg-t1',
        'egg-rare' => 'egg-t2',
        'egg-epic' => 'egg-t3',
    ];

    public function up(): void
    {
        foreach (self::MAPPING as $oldId => $newId) {
            DB::table('item_instances')->where('template_id', $oldId)->update(['template_id' => $newId]);
        }

        DB::table('item_templates')->whereIn('id', array_keys(self::MAPPING))->delete();
    }

    public function down(): void
    {
        // Nieodwracalne - stare szablony zostały scalone z nowymi.
    }
};
