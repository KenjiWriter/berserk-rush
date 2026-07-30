<?php

namespace App\Application\Combat;

use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorldBossService
{
    /**
     * Pula 8 istniejących potworów rangi 'worldboss', przypisana do 3 przedziałów
     * poziomowych (0-35 / 35-65 / 65-99). Co godzinę losowany jest jeden z każdej
     * puli, jeśli dany przedział nie ma aktualnie żywej instancji - patrz
     * docs/modules/world_boss.md.
     */
    public const BRACKET_POOLS = [
        'low' => ['Król Lasu', 'Licz Cieni', 'Król Trolli'],
        'mid' => ['Wódz Orków', 'Moczarowy Behemot'],
        'high' => ['Smok Cienia', 'Arcymag', 'Pan Zniszczenia'],
    ];

    /**
     * Fallback: wyznacza przedział poziomowy na podstawie samego poziomu potwora,
     * używane gdy trzeba dosiać instancję ad-hoc (np. w EncounterService), a nie
     * iterujemy po BRACKET_POOLS.
     */
    public static function bracketForLevel(int $level): string
    {
        if ($level <= 35) {
            return 'low';
        }

        if ($level <= 65) {
            return 'mid';
        }

        return 'high';
    }

    public function tickHourly(): void
    {
        DB::beginTransaction();
        try {
            // Nagrody za world bossa są rozdawane WYŁĄCZNIE przez App\Jobs\WorldBossRewardJob,
            // co godzinę, niezależnie od tego czy boss "padł" w międzyczasie (wspólna pula HP
            // nie regeneruje się, więc wystarczająco duży łączny/pojedynczy dmg może go realnie
            // wyczerpać - patrz docs/modules/world_boss.md). Ten tick ma tylko jedno zadanie:
            // dosiać brakujące instancje bossów (np. dla przedziałów, które aktualnie nie mają
            // żywej instancji), bez ruszania walk w toku.
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
        foreach (array_keys(self::BRACKET_POOLS) as $bracket) {
            $exists = WorldBossInstance::where('level_bracket', $bracket)->exists();

            if (!$exists) {
                $this->spawnBracket($bracket);
            }
        }
    }

    private function spawnWorldBosses(): void
    {
        foreach (array_keys(self::BRACKET_POOLS) as $bracket) {
            $this->spawnBracket($bracket);
        }
    }

    private function spawnBracket(string $bracket): void
    {
        $names = self::BRACKET_POOLS[$bracket];
        $pickedName = $names[array_rand($names)];

        $boss = Monster::where('rank', 'worldboss')->where('name', $pickedName)->first();

        if (!$boss) {
            Log::warning("WorldBossService: nie znaleziono potwora '{$pickedName}' dla przedziału '{$bracket}'.");
            return;
        }

        WorldBossInstance::create([
            'monster_id' => $boss->id,
            'map_id' => $boss->map_id,
            'level_bracket' => $bracket,
            'total_hp' => $boss->stats['hp'] ?? 10000,
            'current_hp' => $boss->stats['hp'] ?? 10000,
        ]);
    }
}
