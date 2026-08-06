<?php

namespace App\Application\Mastery;

use App\Application\Characters\LevelUpService;
use App\Application\Shared\Result;
use App\Domain\Mastery\Events\ChampionLeveledUp;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterChampionSkill;
use App\Infrastructure\Persistence\ChampionSkill;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Facades\DB;

/**
 * System Mistrzostwa (Champion Level) - progresja endgame odblokowana po
 * osiągnięciu 99 poziomu postaci u Czarnoksiężnika. Patrz docs/modules/mastery.md.
 */
class ChampionService
{
    /**
     * Max poziom championa = 99(50). Odpowiada dokładnie puli 50 Punktów
     * Mistrzostwa (1 PK/poziom) możliwych do wydania w drzewku (max 10 pkt/skill,
     * 10 umiejętności).
     */
    public const LEVEL_CAP = 50;

    public const RESET_GOLD_COST = 100_000_000;

    private const MATERIAL_TOTAL_QTY = 1000;
    private const MIN_TIER_COUNT = 3;
    private const MAX_TIER_COUNT = 5;

    private static ?int $cachedXpTarget = null;

    /**
     * Stały próg expa championa dla KAŻDEGO poziomu (99->100 to suma całego expa
     * wymaganego na poziomy 1-99, zgodnie z ustaleniami z designerem). Liczone w
     * locie z krzywej `LevelUpService::xpToNext()` (98 iteracji, tanie), bez
     * hardkodowania - automatycznie podąża za rebalansami krzywej XP. To ten SAM
     * licznik co `character.xp` (jeden pasek EXP) - patrz
     * `LevelUpService::getMaxLevelXpCap()` i docs/modules/mastery.md.
     */
    public function xpTarget(): int
    {
        if (self::$cachedXpTarget !== null) {
            return self::$cachedXpTarget;
        }

        $levelUpService = app(LevelUpService::class);
        $total = 0;
        for ($lvl = 1; $lvl < LevelUpService::MAX_LEVEL; $lvl++) {
            $total += $levelUpService->xpToNext($lvl);
        }

        return self::$cachedXpTarget = $total;
    }

    /**
     * Losuje nowy zestaw wymaganych ulepszaczy (sumujący się do 1000 sztuk) i
     * zapisuje go na postaci. Materiały z niższych map (tier) mają większą wagę
     * (są potrzebne w większej ilości), tak by miały znaczenie w endgame.
     */
    public function rollMaterialRequirements(Character $character): array
    {
        $requirements = $this->buildRandomMaterialRequirements();
        $character->champion_material_progress = $requirements;
        $character->save();

        return $requirements;
    }

    private function buildRandomMaterialRequirements(): array
    {
        $materialsByTier = ItemTemplate::where('type', 'material')
            ->where(fn ($q) => $q->whereNull('sub_type')->orWhere('sub_type', '!=', 'key'))
            ->whereNotNull('source_map_tier')
            ->get(['id', 'name', 'icon', 'source_map_tier'])
            ->groupBy('source_map_tier');

        $availableTiers = $materialsByTier->keys()->sort()->values();
        if ($availableTiers->isEmpty()) {
            return [];
        }

        $maxTier = $availableTiers->max();
        $count = min($availableTiers->count(), mt_rand(self::MIN_TIER_COUNT, self::MAX_TIER_COUNT));
        $chosenTiers = $availableTiers->shuffle()->take($count)->sort()->values();

        // Waga tieru = maxTier+1-tier -> niższy tier = wyższa waga = więcej sztuk wymaganych.
        $weights = $chosenTiers->mapWithKeys(fn ($tier) => [$tier => ($maxTier + 1 - $tier)]);
        $totalWeight = $weights->sum();

        $qtyByTier = [];
        $allocated = 0;
        foreach ($chosenTiers as $tier) {
            if ($tier === $chosenTiers->first()) {
                continue; // najniższy wybrany tier (najwyższa waga) dostaje resztę na końcu
            }
            $qty = max(1, (int) floor(self::MATERIAL_TOTAL_QTY * $weights[$tier] / $totalWeight));
            $qtyByTier[$tier] = $qty;
            $allocated += $qty;
        }
        $qtyByTier[$chosenTiers->first()] = max(1, self::MATERIAL_TOTAL_QTY - $allocated);

        $requirements = [];
        foreach ($chosenTiers as $tier) {
            $template = $materialsByTier[$tier]->random();
            $requirements[] = [
                'template_id' => $template->id,
                'name' => $template->name,
                'icon' => $template->icon,
                'tier' => $tier,
                'required' => $qtyByTier[$tier],
                'deposited' => 0,
            ];
        }

        return $requirements;
    }

    /**
     * Wpłaca ulepszacz do puli wymaganej do awansu championa - konsumuje
     * przedmioty z ekwipunku (stosy, od najmniejszego) i aktualizuje postęp.
     */
    public function donateMaterial(Character $character, string $itemTemplateId, int $quantity): Result
    {
        if ($quantity <= 0) {
            return Result::error('INVALID_QUANTITY', 'Nieprawidłowa ilość.');
        }

        try {
            return DB::transaction(function () use ($character, $itemTemplateId, $quantity) {
                $progress = $character->champion_material_progress ?? [];
                $index = null;
                foreach ($progress as $i => $row) {
                    if ($row['template_id'] === $itemTemplateId) {
                        $index = $i;
                        break;
                    }
                }

                if ($index === null) {
                    return Result::error('NOT_REQUIRED', 'Ten przedmiot nie jest obecnie wymagany przez Czarnoksiężnika.');
                }

                $row = $progress[$index];
                $remaining = $row['required'] - $row['deposited'];
                if ($remaining <= 0) {
                    return Result::error('ALREADY_FULFILLED', 'Wymagana ilość tego ulepszacza została już dostarczona.');
                }

                $toDonate = min($quantity, $remaining);

                $items = ItemInstance::where('owner_character_id', $character->id)
                    ->where('template_id', $itemTemplateId)
                    ->whereIn('location', ['inventory', 'material_stash'])
                    ->where('stack_size', '>', 0)
                    ->get();

                $owned = $items->sum('stack_size');
                if ($owned < $toDonate) {
                    return Result::error('MISSING_ITEM', "Brak wystarczającej ilości w ekwipunku (posiadasz {$owned}, potrzeba {$toDonate}).");
                }

                self::consumeStackedItems($items, $toDonate);

                $row['deposited'] += $toDonate;
                $progress[$index] = $row;
                $character->champion_material_progress = $progress;
                $character->save();

                return Result::ok(['deposited' => $row['deposited'], 'required' => $row['required']]);
            });
        } catch (\Exception $e) {
            return Result::error('DONATE_FAILED', 'Nie udało się przekazać przedmiotu: ' . $e->getMessage());
        }
    }

    /**
     * Wymagana liczba Runicznych Odłamków na dany poziom Czempiona: 1000 + (level * 100).
     */
    public function requiredRunicShards(int $currentChampionLevel): int
    {
        return 1000 + ($currentChampionLevel * 100);
    }

    /**
     * Próba awansu championa - wymaga JEDNOCZEŚNIE pełnego paska expa (ten sam
     * licznik `character.xp` co zwykłe levelowanie, patrz
     * LevelUpService::getMaxLevelXpCap()), dostarczenia wszystkich
     * wylosowanych ulepszaczy ORAZ posiadania wystarczającej liczby Runicznych Odłamków.
     */
    public function attemptLevelUp(Character $character): Result
    {
        if ($character->level < LevelUpService::MAX_LEVEL) {
            return Result::error('LEVEL_TOO_LOW', 'Wymagany jest 99 poziom postaci.');
        }

        if ($character->champion_level >= self::LEVEL_CAP) {
            return Result::error('MAX_CHAMPION_LEVEL', 'Osiągnięto już maksymalny poziom Mistrzostwa.');
        }

        if ($character->xp < $this->xpTarget()) {
            return Result::error('XP_NOT_FULL', 'Pasek doświadczenia nie jest jeszcze pełny.');
        }

        foreach (($character->champion_material_progress ?? []) as $row) {
            if (($row['deposited'] ?? 0) < ($row['required'] ?? 0)) {
                return Result::error('MATERIALS_MISSING', 'Nie wszystkie ulepszacze zostały jeszcze dostarczone Czarnoksiężnikowi.');
            }
        }

        $reqShards = $this->requiredRunicShards($character->champion_level ?? 0);
        $runicTemplate = ItemTemplate::where('name', 'Runiczny Odłamek')->first();
        $runicItems = $runicTemplate
            ? ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $runicTemplate->id)
                ->whereIn('location', ['inventory', 'material_stash'])
                ->where('stack_size', '>', 0)
                ->get()
            : collect();

        $ownedShards = $runicItems->sum('stack_size');
        if ($ownedShards < $reqShards) {
            return Result::error('RUNIC_SHARDS_MISSING', "Do awansu wymagane jest {$reqShards} Runicznych Odłamków (posiadasz {$ownedShards}). Przetapiaj ekwipunek u Kowala, aby zdobyć odłamki.");
        }

        try {
            return DB::transaction(function () use ($character, $runicItems, $reqShards) {
                if ($reqShards > 0 && $runicItems->isNotEmpty()) {
                    self::consumeStackedItems($runicItems, $reqShards);
                }

                $newLevel = $character->champion_level + 1;

                $character->update([
                    'champion_level' => $newLevel,
                    'champion_points' => ($character->champion_points ?? 0) + 1,
                    'xp' => 0,
                    'champion_material_progress' => $newLevel < self::LEVEL_CAP
                        ? $this->buildRandomMaterialRequirements()
                        : null,
                ]);

                event(new ChampionLeveledUp($character->fresh(), $newLevel));

                return Result::ok(['champion_level' => $newLevel]);
            });
        } catch (\Exception $e) {
            return Result::error('LEVEL_UP_FAILED', 'Awans championa nie powiódł się: ' . $e->getMessage());
        }
    }

    /**
     * Inwestuje 1 Punkt Mistrzostwa w daną umiejętność drzewka (max 10 pkt/skill).
     */
    public function investPoint(Character $character, string $championSkillId): Result
    {
        if (($character->champion_points ?? 0) <= 0) {
            return Result::error('NO_POINTS', 'Brak dostępnych Punktów Mistrzostwa.');
        }

        try {
            return DB::transaction(function () use ($character, $championSkillId) {
                $skill = ChampionSkill::find($championSkillId);
                if (!$skill) {
                    return Result::error('SKILL_NOT_FOUND', 'Nie znaleziono umiejętności.');
                }

                $charSkill = CharacterChampionSkill::firstOrCreate(
                    ['character_id' => $character->id, 'champion_skill_id' => $skill->id],
                    ['points' => 0]
                );

                if ($charSkill->points >= $skill->max_points) {
                    return Result::error('MAX_POINTS_REACHED', "Osiągnięto maksymalny poziom umiejętności {$skill->name}.");
                }

                $charSkill->increment('points');
                $character->decrement('champion_points');
                $character->clearChampionCache();

                return Result::ok(['points' => $charSkill->points]);
            });
        } catch (\Exception $e) {
            return Result::error('INVEST_FAILED', 'Nie udało się zainwestować punktu: ' . $e->getMessage());
        }
    }

    /**
     * Reset drzewka Mistrzostwa - płatny (100kk złota), dostępny raz na miesiąc.
     * Zwraca wszystkie zainwestowane punkty do wydania na nowo.
     */
    public function resetSkills(Character $character): Result
    {
        if ($character->last_champion_reset_at && $character->last_champion_reset_at->diffInMonths(now()) < 1) {
            $availableAt = $character->last_champion_reset_at->copy()->addMonth();
            $daysLeft = max(1, (int) ceil(now()->diffInHours($availableAt) / 24));
            return Result::error('RESET_ON_COOLDOWN', "Reset punktów Mistrzostwa dostępny za {$daysLeft} dni (raz na miesiąc).");
        }

        if ($character->gold < self::RESET_GOLD_COST) {
            $costFormatted = number_format(self::RESET_GOLD_COST, 0, ',', ' ');
            return Result::error('NOT_ENOUGH_GOLD', "Brak złota. Wymagane: {$costFormatted} Gold.");
        }

        try {
            return DB::transaction(function () use ($character) {
                $character->decrement('gold', self::RESET_GOLD_COST);
                CharacterChampionSkill::where('character_id', $character->id)->delete();

                $character->update([
                    'champion_points' => $character->champion_level,
                    'last_champion_reset_at' => now(),
                ]);
                $character->clearChampionCache();

                return Result::ok(['champion_points' => $character->champion_points]);
            });
        } catch (\Exception $e) {
            return Result::error('RESET_FAILED', 'Reset nie powiódł się: ' . $e->getMessage());
        }
    }

    /** Zużywa $quantity sztuk z kolekcji ItemInstance (możliwie wielu stosów), od najmniejszego stosu. */
    private static function consumeStackedItems(\Illuminate\Support\Collection $items, int $quantity): void
    {
        $toDeduct = $quantity;
        foreach ($items->sortBy('stack_size') as $item) {
            if ($toDeduct <= 0) {
                break;
            }

            if ($item->stack_size <= $toDeduct) {
                $toDeduct -= $item->stack_size;
                $item->delete();
            } else {
                $item->stack_size -= $toDeduct;
                $item->save();
                $toDeduct = 0;
            }
        }
    }
}
