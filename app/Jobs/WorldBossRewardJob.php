<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorldBossRewardJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Log::info('WorldBossRewardJob: Rozpoczynam rozdawanie nagród.');

        // Pobierz wszystkie instancje bossów, które są pokonane LUB mają logi z obrażeniami i należy je zresetować
        // Zdecydujmy, że po prostu bierzemy wszystkie instancje starsze niż godzinę lub aktualne
        $activeBosses = WorldBossInstance::where('is_defeated', false)->get();

        $keyTemplate = ItemTemplate::firstOrCreate(
            ['type' => 'key', 'name' => 'Klucz do Lochów'],
            [
                'rarity' => 'rare',
                'description' => 'Tajemniczy klucz uprawniający do wejścia do Lochu.',
                'item_level' => 1,
                'is_stackable' => true,
                'icon' => 'key',
                'sell_price_gold' => 100,
            ]
        );

        foreach ($activeBosses as $boss) {
            DB::transaction(function () use ($boss, $keyTemplate) {
                // Oblicz ranking za pomocą bazy danych
                $rankings = WorldBossDamageLog::select('character_id', DB::raw('SUM(damage) as total_damage'))
                    ->where('world_boss_instance_id', $boss->id)
                    ->groupBy('character_id')
                    ->orderByDesc('total_damage')
                    ->take(10)
                    ->get();

                $place = 1;
                foreach ($rankings as $rank) {
                    $keysCount = 0;
                    if ($place == 1) $keysCount = 5;
                    elseif ($place == 2) $keysCount = 4;
                    elseif ($place == 3) $keysCount = 3;
                    elseif ($place >= 4 && $place <= 10) $keysCount = 1;

                    if ($keysCount > 0) {
                        // Create item instance
                        $itemInstance = ItemInstance::create([
                            'item_template_id' => $keyTemplate->id,
                            'character_id' => $rank->character_id,
                            'quantity' => $keysCount,
                            'rarity' => $keyTemplate->rarity,
                            'durability' => 100,
                            'max_durability' => 100,
                        ]);

                        // Send mail
                        MailMessage::create([
                            'receiver_id' => $rank->character_id,
                            'sender_id' => null, // System
                            'subject' => 'Nagroda za Worldbossa',
                            'body' => "Gratulacje! Zająłeś $place miejsce w walce z Worldbossem na mapie {$boss->map->name}. W załączniku przesyłamy klucze.",
                            'attached_item_instance_id' => $itemInstance->id,
                            'attached_gold' => 0,
                            'attached_gems' => 0,
                            'is_read' => false,
                            'has_attachments' => true,
                        ]);
                    }
                    $place++;
                }

                // Oznacz jako pokonany / nieaktywny, by system wygenerował nową instancję przy następnym ataku
                $boss->update(['is_defeated' => true]);
            });
        }
        
        Log::info('WorldBossRewardJob: Zakończono rozdawanie nagród.');
    }
}
