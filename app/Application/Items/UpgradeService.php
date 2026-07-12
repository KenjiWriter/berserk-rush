<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;

class UpgradeService
{
    private array $upgradeChances = [
        0 => 100, // 0 -> 1
        1 => 75,  // 1 -> 2
        2 => 65,  // 2 -> 3
        3 => 55,  // 3 -> 4
        4 => 45,  // 4 -> 5
        5 => 40,  // 5 -> 6
        6 => 35,  // 6 -> 7
        7 => 25,  // 7 -> 8
        8 => 20,  // 8 -> 9
    ];

    public function getUpgradeCost(ItemInstance $item): array
    {
        $level = $item->upgrade_level;
        $baseLevelReq = $item->template->level_requirement;
        
        // Gold cost scales with item level and current upgrade level
        $goldCost = ($baseLevelReq * 50) + ($level * 100) + 100;
        
        $materialName = 'Wilcza Skóra';
        if ($level >= 3 && $level < 6) {
            $materialName = 'Odłamek Kości';
        } elseif ($level >= 6) {
            $materialName = 'Klejnot Pustyni';
        }
        
        $materialTemplate = \App\Infrastructure\Persistence\ItemTemplate::where('name', $materialName)->first();

        return [
            'gold' => $goldCost,
            'chance' => $this->upgradeChances[$level] ?? 0,
            'materials' => $materialTemplate ? [
                [
                    'template_id' => $materialTemplate->id,
                    'name' => $materialTemplate->name,
                    'quantity' => ($level % 3) + 1,
                ]
            ] : []
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

        if ($character->gold < $cost['gold']) {
            return ['success' => false, 'message' => 'Nie masz wystarczająco złota na ulepszenie.'];
        }

        // Check materials
        $inventoryMaterials = $character->inventoryItems()->whereIn('template_id', array_column($cost['materials'], 'template_id'))->get();
        
        foreach ($cost['materials'] as $reqMat) {
            $owned = $inventoryMaterials->where('template_id', $reqMat['template_id'])->sum('stack_size');
            if ($owned < $reqMat['quantity']) {
                return ['success' => false, 'message' => "Brakuje materiałów: {$reqMat['name']} (Posiadasz {$owned}/{$reqMat['quantity']})"];
            }
        }
        
        $character->gold -= $cost['gold'];
        $character->save();

        // Deduct materials
        foreach ($cost['materials'] as $reqMat) {
            $toDeduct = $reqMat['quantity'];
            $instances = $inventoryMaterials->where('template_id', $reqMat['template_id']);
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
            // Failure: Only lose gold/materials
            return [
                'success' => false, 
                'message' => "Ulepszenie nie powiodło się. Straciłeś materiały, ale przedmiot pozostaje nietknięty."
            ];
        }
    }
}
