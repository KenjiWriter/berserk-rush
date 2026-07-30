<?php

namespace App\Jobs;

use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Mail;
use App\Application\Combat\WorldBossService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UWAGA (fix 2026-07-30): ta klasa CELOWO NIE implementuje ShouldQueue. Wcześniej
 * `Schedule::job(new WorldBossRewardJob())->hourly()` (routes/console.php) kolejkował ten job,
 * co wymagało DZIAŁAJĄCEGO, OSOBNEGO procesu queue workera (`php artisan queue:work`), żeby w
 * ogóle się wykonał. Jeśli worker nie działał/padł, harmonogram i tak "odpalał się" punktualnie
 * co godzinę, ale zadanie tylko trafiało do kolejki i nigdy się nie wykonywało - dokładnie tak
 * zgłosił użytkownik (mimo upływu godziny bossy się nie resetowały, nagrody nie przyszły). Bez
 * ShouldQueue, `Schedule::job()` wykonuje handle() synchronicznie w samym procesie schedulera,
 * więc jedyną zależnością infrastrukturalną zostaje działający cron (`php artisan
 * schedule:run`), bez potrzeby utrzymywania osobnego workera. Praca tego joba jest lekka (max 3
 * instancje world bossów na wywołanie), więc kolejkowanie nie dawało tu realnej korzyści
 * wydajnościowej, za to dodawało ryzyko cichej awarii.
 */
class WorldBossRewardJob
{

    /**
     * World boss (po nazwie potwora) -> nazwa ItemTemplate klucza do najbliższego
     * poziomowo lochu (patrz database/seeders/DungeonSeeder.php). Trzymamy mapowanie
     * po nazwie, żeby nie duplikować twardo zakodowanych ULID-ów kluczy.
     */
    const MONSTER_KEY_MAP = [
        'Król Lasu' => 'Klucz Katakumb',
        'Licz Cieni' => 'Klucz Krypty',
        'Król Trolli' => 'Klucz Krypty',
        'Wódz Orków' => 'Klucz Pustkowi',
        'Moczarowy Behemot' => 'Klucz Cytadeli',
        'Smok Cienia' => 'Klucz Cytadeli',
        'Arcymag' => 'Klucz Otchłani',
        'Pan Zniszczenia' => 'Klucz Otchłani',
    ];

    /**
     * Jedyne źródło prawdy dla tabeli nagród (miejsce -> gemy/klucze). Używane zarówno
     * tutaj przy faktycznym rozdawaniu, jak i w UI (Adventure.php/adventure.blade.php)
     * do pokazania graczowi, o co właściwie walczy, zanim nagrody zostaną rozdane.
     *
     * @return array{gems: int, keys: int}
     */
    public static function rewardForPlace(int $place): array
    {
        [$gems, $keys] = match(true) {
            $place === 1 => [50, 5],
            $place >= 2 && $place <= 3 => [30, 5],
            $place >= 4 && $place <= 6 => [0, 3],
            $place >= 7 && $place <= 9 => [0, 1],
            default => [0, 0],
        };

        return ['gems' => $gems, 'keys' => $keys];
    }

    public function handle(): void
    {
        Log::info('WorldBossRewardJob: Rozpoczynam rozdawanie nagród.');

        // UWAGA (rework world bossów): world boss regeneruje HP i teoretycznie nigdy nie
        // da się go "zabić" - is_defeated nie istnieje już w schemacie. Rozliczenie jest
        // więc czysto czasowe: co godzinę (ten job jest Schedule::job(...)->hourly())
        // KAŻDY z 3 przedziałów resetuje się bezwarunkowo - jeśli ktoś zdążył zadać dmg,
        // nagradzamy TOP 9 po zadanym DMG, po czym zawsze kasujemy instancję i losujemy
        // nowego bossa na kolejną godzinę, niezależnie od aktywności - patrz
        // docs/modules/world_boss.md.
        $activeBosses = WorldBossInstance::with('monster')->get();

        if ($activeBosses->isEmpty()) {
            Log::info('WorldBossRewardJob: Brak aktywnych instancji world bossów.');
            app(WorldBossService::class)->ensureBossesSpawned();
            return;
        }

        foreach ($activeBosses as $boss) {
            DB::transaction(function () use ($boss) {
                $monsterName = $boss->monster->name ?? null;
                $keyTemplateName = self::MONSTER_KEY_MAP[$monsterName] ?? null;
                $keyTemplate = $keyTemplateName ? ItemTemplate::where('name', $keyTemplateName)->first() : null;

                if (!$keyTemplate) {
                    Log::error("WorldBossRewardJob: Nie znaleziono szablonu klucza dla bossa '{$monsterName}'.");
                }

                // Oblicz ranking po łącznych obrażeniach (jeśli nikt nie walczył, pusta kolekcja)
                $rankings = WorldBossDamageLog::select('character_id', DB::raw('SUM(damage) as total_damage'))
                    ->where('world_boss_instance_id', $boss->id)
                    ->groupBy('character_id')
                    ->orderByDesc('total_damage')
                    ->take(9)
                    ->get();

                $place = 1;
                foreach ($rankings as $rank) {
                    $reward = self::rewardForPlace($place);
                    $gems = $reward['gems'];
                    $keys = $reward['keys'];

                    if ($gems > 0 || $keys > 0) {
                        $attachments = [];
                        $bodyParts = [];

                        if ($gems > 0) {
                            $attachments[] = ['type' => 'gems', 'qty' => $gems];
                            $bodyParts[] = "{$gems} gemów";
                        }

                        if ($keys > 0 && $keyTemplate) {
                            $itemInstance = ItemInstance::create([
                                'template_id'        => $keyTemplate->id,
                                'owner_character_id' => $rank->character_id,
                                'stack_size'         => $keys,
                                'rarity'             => 'uncommon',
                                'location'           => 'mail',
                            ]);

                            $attachments[] = ['type' => 'item', 'id' => $itemInstance->id];
                            $bodyParts[] = "{$keys} " . ($keys === 1 ? 'klucz' : 'klucze') . " ({$keyTemplate->name})";
                        }

                        $mapName = $boss->map->name ?? 'nieznanej mapie';
                        Mail::create([
                            'to_character_id' => $rank->character_id,
                            'subject'         => 'Nagroda za Worldbossa',
                            'body'            => "Gratulacje! Zająłeś {$place} miejsce w walce z Worldbossem ({$monsterName}) na mapie {$mapName}. Otrzymujesz: " . implode(', ', $bodyParts) . '.',
                            'attachments'     => $attachments,
                            'claimed'         => false,
                        ]);

                        Log::info("WorldBossRewardJob: Nagroda wysłana do postaci {$rank->character_id} (miejsce {$place}): " . implode(', ', $bodyParts));
                    }

                    $place++;
                }

                // Nagrody rozdane - usuń logi obrażeń i samą instancję, żeby respawn poniżej
                // zawsze stworzył dla tego przedziału świeżą (być może inną, losowo wybraną)
                // instancję z pełnym HP.
                WorldBossDamageLog::where('world_boss_instance_id', $boss->id)->delete();
                $boss->delete();

                Log::info("WorldBossRewardJob: Boss {$boss->id} ({$monsterName}) rozliczony, nagrody rozdane, instancja i logi wyczyszczone.");
            });
        }

        // Respawnuj bossów dla przedziałów, których instancje właśnie usunęliśmy (i wszelkich
        // innych brakujących), żeby żaden przedział nie został bez world bossa.
        app(WorldBossService::class)->ensureBossesSpawned();

        Log::info('WorldBossRewardJob: Zakończono rozdawanie nagród.');
    }
}
