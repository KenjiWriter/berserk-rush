<?php

namespace App\Application\Mirror;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\CharacterMapMirrorStat;
use App\Infrastructure\Persistence\CharacterMirrorSession;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Characters\LevelUpService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MirrorService
{
    public function __construct(
        private LevelUpService $levelUpService
    ) {}

    /**
     * Retrieve stored rates or calculate baseline map rates for a character.
     */
    public function getMapRates(Character $character, Map $map): array
    {
        $stat = CharacterMapMirrorStat::where('character_id', $character->id)
            ->where('map_id', $map->id)
            ->first();

        if ($stat && $stat->max_exp_per_minute > 0 && $stat->max_gold_per_minute > 0) {
            return [
                'exp_per_minute' => $stat->max_exp_per_minute,
                'gold_per_minute' => $stat->max_gold_per_minute,
                'is_custom' => true,
            ];
        }

        // Calculate baseline rates based on map level_min/max and exploration monsters
        $monsters = $map->explorationMonsters;

        if ($monsters->isNotEmpty()) {
            $avgLevel = $monsters->avg('level') ?: ($map->level_min ?: 1);
        } else {
            $avgLevel = $map->level_min ?: 1;
        }

        // Baseline rate formula per minute (assuming ~8 kills per min)
        $baseExpPerMin = (int) round(($avgLevel * 18 + 30) * 8);
        $baseGoldPerMin = (int) round(($avgLevel * 10 + 15) * 8);

        // Ensure minimums
        $baseExpPerMin = max(100, $baseExpPerMin);
        $baseGoldPerMin = max(50, $baseGoldPerMin);

        // Save baseline if missing
        if (!$stat) {
            CharacterMapMirrorStat::create([
                'character_id' => $character->id,
                'map_id' => $map->id,
                'max_exp_per_minute' => $baseExpPerMin,
                'max_gold_per_minute' => $baseGoldPerMin,
            ]);
        }

        return [
            'exp_per_minute' => $stat ? $stat->max_exp_per_minute : $baseExpPerMin,
            'gold_per_minute' => $stat ? $stat->max_gold_per_minute : $baseGoldPerMin,
            'is_custom' => false,
        ];
    }

    /**
     * Update max rates for a character on a map when they perform combat.
     */
    public function updateMapRates(Character $character, Map $map, int $expPerMin, int $goldPerMin): void
    {
        if ($expPerMin <= 0 && $goldPerMin <= 0) {
            return;
        }

        $stat = CharacterMapMirrorStat::firstOrCreate(
            ['character_id' => $character->id, 'map_id' => $map->id],
            ['max_exp_per_minute' => 0, 'max_gold_per_minute' => 0]
        );

        $newExp = max($stat->max_exp_per_minute, $expPerMin);
        $newGold = max($stat->max_gold_per_minute, $goldPerMin);

        if ($newExp !== $stat->max_exp_per_minute || $newGold !== $stat->max_gold_per_minute) {
            $stat->update([
                'max_exp_per_minute' => $newExp,
                'max_gold_per_minute' => $newGold,
            ]);
        }
    }

    /**
     * Start a new Mirror session.
     */
    public function startMirror(Character $character, Map $map, int $durationHours): CharacterMirrorSession
    {
        if ($character->hasActiveMirror()) {
            throw new \InvalidArgumentException('Masz już aktywne Lustro.');
        }

        if (!$map->isAccessibleBy($character)) {
            throw new \InvalidArgumentException('Twój poziom postaci jest za niski na wybraną mapę.');
        }

        $maxHours = ($character->user && $character->user->hasPremium()) ? 10 : 6;

        if ($durationHours < 1 || $durationHours > $maxHours) {
            throw new \InvalidArgumentException("Czas trwania lustra musi wynosić od 1 do {$maxHours} godzin.");
        }

        $rates = $this->getMapRates($character, $map);

        return CharacterMirrorSession::create([
            'character_id' => $character->id,
            'map_id' => $map->id,
            'duration_hours' => $durationHours,
            'exp_per_minute' => $rates['exp_per_minute'],
            'gold_per_minute' => $rates['gold_per_minute'],
            'started_at' => now(),
            'ends_at' => now()->addHours($durationHours),
            'status' => 'active',
        ]);
    }

    /**
     * Stop the current active mirror session and grant calculated rewards.
     */
    public function stopAndClaim(Character $character): array
    {
        $session = $character->activeMirrorSession;

        if (!$session) {
            throw new \InvalidArgumentException('Nie masz aktywnego Lustra do przerwania.');
        }

        return DB::transaction(function () use ($character, $session) {
            $session->load('map');
            $rewards = $session->calculateCurrentRewards();

            // Grant EXP
            if ($rewards['xp'] > 0) {
                $this->levelUpService->addXp($character, $rewards['xp']);
            }

            // Grant Gold
            if ($rewards['gold'] > 0) {
                $character->increment('gold', $rewards['gold']);
            }

            // Grant Materials
            $grantedMaterials = [];
            foreach ($rewards['materials'] as $matId => $matInfo) {
                $template = $matInfo['item_template'];
                $qty = $matInfo['quantity'];

                if ($qty <= 0) continue;

                $location = ($template->type === 'material') ? 'material_stash' : 'inventory';

                $existingItem = ItemInstance::where('owner_character_id', $character->id)
                    ->where('template_id', $template->id)
                    ->whereIn('location', ['material_stash', 'inventory'])
                    ->first();

                if ($existingItem) {
                    $existingItem->location = $location;
                    $existingItem->stack_size += $qty;
                    $existingItem->save();
                } else {
                    ItemInstance::create([
                        'id' => (string) Str::ulid(),
                        'owner_character_id' => $character->id,
                        'template_id' => $template->id,
                        'location' => $location,
                        'stack_size' => $qty,
                    ]);
                }

                $grantedMaterials[] = [
                    'name' => $template->name,
                    'quantity' => $qty,
                    'icon' => $template->icon ?? 'material.png',
                ];
            }

            // Mark session as claimed
            $session->update([
                'status' => 'claimed',
                'claimed_at' => now(),
            ]);

            return [
                'session' => $session,
                'map_name' => $session->map ? $session->map->name : 'Przygoda',
                'elapsed_minutes' => $rewards['elapsed_minutes'],
                'xp' => $rewards['xp'],
                'gold' => $rewards['gold'],
                'materials' => $grantedMaterials,
            ];
        });
    }
}
