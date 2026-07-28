<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Zwiększ czas trwania buffu Eliksiru Wiedzy Absolutnej z 10 minut do 60 minut (1 godzina)
        $template = DB::table('item_templates')->where('id', 'potion-exp-special')->first();

        if ($template && $template->base_stats) {
            $baseStats = json_decode($template->base_stats, true) ?? [];
            $baseStats['duration_minutes'] = 60;

            DB::table('item_templates')
                ->where('id', 'potion-exp-special')
                ->update(['base_stats' => json_encode($baseStats)]);
        }
    }

    public function down(): void
    {
        $template = DB::table('item_templates')->where('id', 'potion-exp-special')->first();

        if ($template && $template->base_stats) {
            $baseStats = json_decode($template->base_stats, true) ?? [];
            $baseStats['duration_minutes'] = 10;

            DB::table('item_templates')
                ->where('id', 'potion-exp-special')
                ->update(['base_stats' => json_encode($baseStats)]);
        }
    }
};
