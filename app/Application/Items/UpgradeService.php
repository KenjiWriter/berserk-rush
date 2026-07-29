<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;

class UpgradeService
{
    // Removed hardcoded upgradeChances

    public function getUpgradeCost(ItemInstance $item): ?array
    {
        $level = $item->upgrade_level;
        
        // Priority 1: Specific template rule
        $rule = \App\Infrastructure\Persistence\UpgradeRule::where('from_level', $level)
            ->where('applies_to', 'template')
            ->where('applies_value', $item->template->id)
            ->first();

        // Priority 2: Fallback rule (slot, type, or rarity)
        if (!$rule) {
            $rule = \App\Infrastructure\Persistence\UpgradeRule::where('from_level', $level)
                ->where(function($q) use ($item) {
                    $q->where(function($q2) use ($item) {
                        $q2->where('applies_to', 'slot')->where('applies_value', $item->template->slot);
                    })
                    ->orWhere(function($q2) use ($item) {
                        $q2->where('applies_to', 'type')->where('applies_value', $item->template->type);
                    })
                    ->orWhere(function($q2) use ($item) {
                        $q2->where('applies_to', 'rarity')->where('applies_value', $item->rarity);
                    });
                })
                ->first();
        }

        if (!$rule) {
            return null; // No rule found
        }

        $materials = [];
        if (isset($rule->cost['materials']) && is_array($rule->cost['materials'])) {
            foreach ($rule->cost['materials'] as $mat) {
                $template = \App\Infrastructure\Persistence\ItemTemplate::find($mat['template_id']);
                if ($template) {
                    $dropMonsters = \App\Infrastructure\Persistence\Monster::whereHas('lootTable.entries', function($q) use ($template) {
                        $q->where('ref_ulid', $template->id);
                    })->with('map')->get()->map(function ($monster) {
                        return [
                            'monster' => $monster->name,
                            'map' => $monster->map->name ?? null,
                        ];
                    })->toArray();

                    $materials[] = [
                        'template_id' => $template->id,
                        'name' => $template->name,
                        'icon' => $template->icon,
                        'quantity' => $mat['quantity'],
                        'dropped_by' => $dropMonsters,
                    ];
                }
            }
        }

        $eventService = app(\App\Application\Events\WeekendEventService::class);
        $eventBonusChance = $eventService->getUpgradeBonusChance() * 100; // e.g. 15%
        $totalChance = min(100, ($rule->success_chance * 100) + $eventBonusChance);

        return [
            'gold' => $rule->cost['gold'] ?? 0,
            'chance' => $totalChance,
            'materials' => $materials,
            'on_fail' => $rule->on_fail,
            'rule_id' => $rule->id,
        ];
    }

    public function upgradeItem(Character $character, ItemInstance $item): array
    {
        if ($item->owner_character_id !== $character->id) {
            return ['success' => false, 'message' => 'Nie jesteś właścicielem tego przedmiotu.'];
        }

        if ($item->upgrade_level >= 9) {
            return ['success' => false, 'message' => 'Przedmiot osiągnął maksymalny poziom ulepszenia.'];
        }

        $cost = $this->getUpgradeCost($item);
        if (!$cost) {
            return ['success' => false, 'message' => 'Brak zasad ulepszania dla tego przedmiotu na obecnym poziomie.'];
        }

        if ($character->gold < $cost['gold']) {
            return ['success' => false, 'message' => 'Nie masz wystarczająco złota na ulepszenie.'];
        }

        // Check materials from material_stash or inventory
        $availableMaterials = $character->items()
            ->whereIn('location', ['material_stash', 'inventory'])
            ->whereIn('template_id', array_column($cost['materials'], 'template_id'))
            ->get();
        
        foreach ($cost['materials'] as $reqMat) {
            $owned = $availableMaterials->where('template_id', $reqMat['template_id'])->sum('stack_size');
            if ($owned < $reqMat['quantity']) {
                return ['success' => false, 'message' => "Brakuje materiałów: {$reqMat['name']} (Posiadasz {$owned}/{$reqMat['quantity']})"];
            }
        }
        
        $character->gold -= $cost['gold'];
        $character->save();

        // Deduct materials
        foreach ($cost['materials'] as $reqMat) {
            $toDeduct = $reqMat['quantity'];
            $instances = $availableMaterials->where('template_id', $reqMat['template_id'])->sortBy('stack_size');
            foreach ($instances as $matInstance) {
                if ($toDeduct <= 0) break;
                
                if ($matInstance->stack_size <= $toDeduct) {
                    $toDeduct -= $matInstance->stack_size;
                    $matInstance->delete();
                } else {
                    $matInstance->stack_size -= $toDeduct;
                    $matInstance->save();
                    $toDeduct = 0;
                }
            }
        }

        $chance = $cost['chance'];
        $roll = mt_rand(1, 100);

        if ($roll <= $chance) {
            $item->upgrade_level += 1;
            $item->save();

            // Quest progress
            app(\App\Application\Quests\QuestService::class)->progressQuest(
                $character, 
                'action', 
                ['upgrade_item', 'upgrade_to_' . $item->upgrade_level]
            );

            return [
                'success' => true, 
                'message' => "Ulepszenie zakończone sukcesem! {$item->template->name} ma teraz poziom +{$item->upgrade_level}."
            ];
        } else {
            // Failure logic
            $failAction = $cost['on_fail'] ?? 'nothing';
            $failMessage = "Ulepszenie nie powiodło się. Straciłeś materiały.";

            if ($failAction === 'downgrade' && $item->upgrade_level > 0) {
                $item->upgrade_level -= 1;
                $item->save();
                $failMessage .= " Poziom przedmiotu spadł do +{$item->upgrade_level}.";
            } elseif ($failAction === 'break') {
                $item->delete();
                $failMessage .= " Przedmiot został ZNISZCZONY!";
            } else {
                $failMessage .= " Przedmiot pozostaje nietknięty.";
            }

            return [
                'success' => false, 
                'message' => $failMessage
            ];
        }
    }
}
