<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Mail;
use App\Application\Combat\WorldBossService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorldBossRewardJob implements ShouldQueue
{
    use Queueable;

    /**
     * ID szablonu klucza do lochów (Zardzewiały Klucz do Lochów).
     * Musi pasować do entry_item_template_id w tabeli dungeons.
     */
    const DUNGEON_KEY_TEMPLATE_ID = '01k4jpx94j70x2vv10b835key1';

    public function handle(): void
    {
        Log::info('WorldBossRewardJob: Rozpoczynam rozdawanie nagród.');

        // Pobierz szablon klucza po stałym ID zdefiniowanym w ItemTemplateSeeder
        $keyTemplate = ItemTemplate::find(self::DUNGEON_KEY_TEMPLATE_ID);

        if (!$keyTemplate) {
            Log::error('WorldBossRewardJob: Nie znaleziono szablonu klucza (ID: ' . self::DUNGEON_KEY_TEMPLATE_ID . '). Uruchom ItemTemplateSeeder.');
            return;
        }

        // UWAGA (fix 2026-07-28): warunek był odwrócony - `is_defeated = false`
        // oznaczał "jeszcze nie nagrodzony", więc ten job co godzinę siłowo
        // rozstrzygał WSZYSTKIE toczące się walki (nawet gdy boss wciąż żył),
        // rozdawał nagrody i resetował instancję, zanim gracze zdążyli go
        // realnie pokonać. Teraz reagujemy wyłącznie na bossów faktycznie
        // pokonanych (is_defeated = true, ustawiane przez walkę gracza po
        // wyzerowaniu current_hp), zgodnie z docs/modules/world_boss.md.
        $defeatedBosses = WorldBossInstance::where('is_defeated', true)
            ->whereHas('damageLogs')
            ->get();

        if ($defeatedBosses->isEmpty()) {
            Log::info('WorldBossRewardJob: Brak pokonanych bossów oczekujących na nagrody.');
            return;
        }

        foreach ($defeatedBosses as $boss) {
            DB::transaction(function () use ($boss, $keyTemplate) {
                // Oblicz ranking po łącznych obrażeniach
                $rankings = WorldBossDamageLog::select('character_id', DB::raw('SUM(damage) as total_damage'))
                    ->where('world_boss_instance_id', $boss->id)
                    ->groupBy('character_id')
                    ->orderByDesc('total_damage')
                    ->take(10)
                    ->get();

                $place = 1;
                foreach ($rankings as $rank) {
                    $keysCount = match(true) {
                        $place === 1              => 5,
                        $place === 2              => 4,
                        $place === 3              => 3,
                        $place >= 4 && $place <= 10 => 1,
                        default                   => 0,
                    };

                    if ($keysCount > 0) {
                        // Utwórz instancję klucza w lokalizacji "mail" (oczekuje na odebranie)
                        $itemInstance = ItemInstance::create([
                            'template_id'        => $keyTemplate->id,
                            'owner_character_id' => $rank->character_id,
                            'stack_size'         => $keysCount,
                            'rarity'             => 'uncommon',
                            'location'           => 'mail',
                        ]);

                        // Wyślij wiadomość systemową z załącznikiem
                        $mapName = $boss->map->name ?? 'nieznanej mapie';
                        Mail::create([
                            'to_character_id' => $rank->character_id,
                            'subject'         => 'Nagroda za Worldbossa',
                            'body'            => "Gratulacje! Zająłeś $place miejsce w walce z Worldbossem na mapie {$mapName}. Otrzymujesz {$keysCount} " . ($keysCount === 1 ? 'klucz' : 'klucze') . " do Lochów.",
                            'attachments'     => [
                                ['type' => 'item', 'id' => $itemInstance->id],
                            ],
                            'claimed'         => false,
                        ]);

                        Log::info("WorldBossRewardJob: Wysłano {$keysCount} klucz(e) do postaci {$rank->character_id} (miejsce {$place}).");
                    }

                    $place++;
                }

                // Nagrody rozdane - usuń logi obrażeń i samą instancję (a nie tylko flagę),
                // żeby nie zostawiać martwych rekordów i żeby respawn poniżej zawsze stworzył
                // dla tej mapy świeżą, w pełni żywą instancję z pełnym HP.
                WorldBossDamageLog::where('world_boss_instance_id', $boss->id)->delete();
                $boss->delete();

                Log::info("WorldBossRewardJob: Boss {$boss->id} pokonany, nagrody rozdane, instancja i logi wyczyszczone.");
            });
        }

        // Respawnuj bossów, których instancje właśnie usunęliśmy (i wszelkich innych
        // brakujących), żeby mapa nie została bez world bossa.
        app(WorldBossService::class)->ensureBossesSpawned();

        Log::info('WorldBossRewardJob: Zakończono rozdawanie nagród.');
    }
}
