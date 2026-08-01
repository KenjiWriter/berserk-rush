<?php

namespace App\Application\Items;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterEquipmentSetItem;
use App\Infrastructure\Persistence\CharacterSkillSetItem;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EquipmentSetService
{
    /**
     * Zapisuje aktualnie założony ekwipunek i umiejętności postaci jako nazwany zestaw
     * (pvp/guild_war/set_1/set_2/set_3). Nadpisuje poprzednią zawartość tego setu.
     */
    public function saveCurrentAsSet(Character $character, string $setType): Result
    {
        if (!in_array($setType, CharacterEquipmentSetItem::ALL_SETS, true)) {
            return Result::error('INVALID_SET', 'Nieznany typ zestawu.');
        }

        if (in_array($setType, CharacterEquipmentSetItem::VIP_ONLY_SETS, true) && !$character->user?->hasPremium()) {
            return Result::error('VIP_REQUIRED', 'Ten zestaw jest dostępny wyłącznie dla graczy Premium.');
        }

        return DB::transaction(function () use ($character, $setType) {
            $character->equipmentSetItemsFor($setType)->delete();

            $equipped = $character->equippedItems()->get();
            foreach ($equipped as $item) {
                $slot = $item->template?->slot;
                if (!$slot) {
                    continue;
                }

                CharacterEquipmentSetItem::create([
                    'character_id' => $character->id,
                    'set_type' => $setType,
                    'slot' => $slot,
                    'item_instance_id' => $item->id,
                ]);
            }

            // Zapis umiejętności do presetu
            $character->skillSetItemsFor($setType)->delete();
            $equippedSkills = $character->equippedSkills()->get();
            foreach ($equippedSkills as $idx => $charSkill) {
                CharacterSkillSetItem::create([
                    'character_id' => $character->id,
                    'set_type' => $setType,
                    'equip_slot' => $idx + 1,
                    'combat_skill_id' => $charSkill->combat_skill_id,
                ]);
            }

            Log::info('Equipment & skill set saved', ['character_id' => $character->id, 'set_type' => $setType]);

            return Result::ok(['set_type' => $setType]);
        });
    }

    /**
     * Fizycznie zakłada zapisany zestaw (tylko set_1/set_2/set_3 - Arena/Gildia
     * nigdy nie są "zakładane", służą wyłącznie do wyliczeń obrony/wojny).
     * Sloty, dla których zapisany przedmiot już nie istnieje/nie należy do
     * postaci, zostają bez zmian (nie są zdejmowane).
     */
    public function applySet(Character $character, string $setType): Result
    {
        if (!in_array($setType, CharacterEquipmentSetItem::WEARABLE_SETS, true)) {
            return Result::error('NOT_WEARABLE', 'Ten zestaw nie jest przeznaczony do noszenia.');
        }

        if (in_array($setType, CharacterEquipmentSetItem::VIP_ONLY_SETS, true) && !$character->user?->hasPremium()) {
            return Result::error('VIP_REQUIRED', 'Ten zestaw jest dostępny wyłącznie dla graczy Premium.');
        }

        try {
            return DB::transaction(function () use ($character, $setType) {
                $saved = $character->equipmentSetItemsFor($setType)->get()->keyBy('slot');
                $currentlyEquipped = $character->equippedItems()->get()->keyBy(fn ($item) => $item->template?->slot);

                foreach (CharacterEquipmentSetItem::SLOTS as $slot) {
                    $targetItem = $saved->get($slot)?->itemInstance;

                    if (!$targetItem || $targetItem->owner_character_id !== $character->id) {
                        continue; // slot nieskonfigurowany albo przedmiot już nie nasz - zostaw jak jest
                    }

                    $current = $currentlyEquipped->get($slot);
                    if ($current && $current->id === $targetItem->id) {
                        continue; // już założony
                    }

                    if ($current) {
                        $current->update(['location' => 'inventory', 'bound_to_character' => false]);
                    }

                    $targetItem->update(['location' => 'equipped', 'bound_to_character' => true]);
                }

                // Zastosowanie zapisanego zestawu umiejętności (jeśli istnieje)
                $savedSkills = $character->skillSetItemsFor($setType)->get();
                if ($savedSkills->isNotEmpty()) {
                    $targetSkillIds = $savedSkills->pluck('combat_skill_id')->toArray();
                    CharacterCombatSkill::where('character_id', $character->id)->update(['is_equipped' => false]);
                    CharacterCombatSkill::where('character_id', $character->id)
                        ->whereIn('combat_skill_id', $targetSkillIds)
                        ->update(['is_equipped' => true]);
                }

                $character->clearStatsCache();

                Log::info('Equipment set applied', ['character_id' => $character->id, 'set_type' => $setType]);

                return Result::ok(['set_type' => $setType]);
            });
        } catch (\Exception $e) {
            Log::error('EquipmentSetService::applySet failed', [
                'character_id' => $character->id,
                'set_type' => $setType,
                'error' => $e->getMessage(),
            ]);

            return Result::error('APPLY_FAILED', 'Wystąpił błąd podczas zakładania zestawu.');
        }
    }
}
