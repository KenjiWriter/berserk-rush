<?php

namespace App\Application\Combat;

use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorldBossService
{
    public function tickHourly(): void
    {
        DB::beginTransaction();
        try {
            // Nagrody za pokonanie world bossa są rozdawane WYŁĄCZNIE przez
            // App\Jobs\WorldBossRewardJob (reaguje na realne zwycięstwo graczy,
            // czyści instancję/logi i respawnuje danego bossa po fakcie - patrz
            // docs/modules/world_boss.md). Wcześniej ten tick bezwarunkowo
            // rozdawał nagrody i kasował WSZYSTKIE instancje co godzinę,
            // niezależnie od tego czy boss faktycznie padł, przez co współdzielony
            // pasek HP resetował się zanim gracze zdążyli go realnie wyzerować -
            // to był główny powód, dla którego world bossy "nie dawało się zabić".
            //
            // Ten tick ma teraz jedno zadanie: dosiać brakujące instancje bossów
            // (np. dla nowo dodanych map albo tych usuniętych przez
            // WorldBossRewardJob po pokonaniu), bez ruszania walk w toku.
            $this->ensureBossesSpawned();

            DB::commit();
            Log::info("WorldBossTick executed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("WorldBossTick failed: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function resetBosses(): void
    {
        DB::beginTransaction();
        try {
            WorldBossDamageLog::query()->delete();
            WorldBossInstance::query()->delete();

            $this->spawnWorldBosses();

            DB::commit();
            Log::info("WorldBosses reset successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("WorldBosses reset failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function ensureBossesSpawned(): void
    {
        $worldBosses = Monster::where('rank', 'worldboss')->get();

        foreach ($worldBosses as $boss) {
            $exists = WorldBossInstance::where('monster_id', $boss->id)
                ->where('is_defeated', false)
                ->exists();

            if (!$exists) {
                WorldBossInstance::create([
                    'monster_id' => $boss->id,
                    'map_id' => $boss->map_id,
                    'total_hp' => $boss->stats['hp'] ?? 10000,
                    'current_hp' => $boss->stats['hp'] ?? 10000,
                    'is_defeated' => false,
                ]);
            }
        }
    }

    // UWAGA (fix 2026-07-28): dawniej istniała tu metoda distributeRewards(),
    // wywoływana co godzinę z tickHourly() niezależnie od tego czy world boss
    // faktycznie został pokonany. Rozdawanie nagród (kluczy) za pokonanie world
    // bossa jest teraz WYŁĄCZNIE odpowiedzialnością App\Jobs\WorldBossRewardJob,
    // który reaguje na realne zwycięstwo (is_defeated = true) zamiast siłowo
    // rozstrzygać walki w toku. Metodę usunięto, żeby uniknąć dwóch
    // konkurujących ze sobą systemów nagród - patrz docs/modules/world_boss.md.

    private function spawnWorldBosses(): void
    {
        // Get all world boss monsters
        $worldBosses = Monster::where('rank', 'worldboss')->get();

        foreach ($worldBosses as $boss) {
            WorldBossInstance::create([
                'monster_id' => $boss->id,
                'map_id' => $boss->map_id,
                'total_hp' => $boss->stats['hp'] ?? 10000,
                'current_hp' => $boss->stats['hp'] ?? 10000,
                'is_defeated' => false,
            ]);
        }
    }
}
