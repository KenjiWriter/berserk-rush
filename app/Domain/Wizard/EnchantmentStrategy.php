<?php

namespace App\Domain\Wizard;

use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\RNG\RandomProvider;

class EnchantmentStrategy
{
    private array $successRates = [
        0 => 75,
        1 => 50,
        2 => 40,
        3 => 30,
        4 => 20,
    ];

    private array $weaponBonuses = [
        'attack_power' => [10, 50],
        'magic_attack' => [10, 50],
        'crit_chance' => [1, 10],
        'strong_vs_demons' => [5, 20],
        'strong_vs_undead' => [5, 20],
        'strong_vs_animals' => [5, 20],
        'strong_vs_orcs' => [5, 20],
    ];

    private array $armorBonuses = [
        'hp_bonus' => [50, 350],
        'defense' => [5, 30],
        'dodge_chance' => [1, 5],
        'resist_demons' => [2, 10],
        'resist_undead' => [2, 10],
        'resist_animals' => [2, 10],
        'resist_orcs' => [2, 10],
    ];

    public function __construct(private RandomProvider $rng)
    {}

    public function canEnchant(ItemInstance $item): bool
    {
        $currentEnchants = count($item->getEnchantments());
        return $currentEnchants < 5;
    }

    public function getSuccessChance(ItemInstance $item): int
    {
        $currentEnchants = count($item->getEnchantments());
        return $this->successRates[$currentEnchants] ?? 0;
    }

    public function tryEnchant(ItemInstance $item): bool
    {
        if (!$this->canEnchant($item)) {
            return false;
        }

        $chance = $this->getSuccessChance($item);
        $roll = $this->rng->int(1, 100);
        return $roll <= $chance;
    }

    public function generateRandomEnchantment(ItemInstance $item): array
    {
        $type = $item->template->type;
        $isWeapon = in_array($type, ['sword', 'staff', 'bow', 'weapon']);
        $pool = $isWeapon ? $this->weaponBonuses : $this->armorBonuses;

        $currentEnchants = array_keys($item->getEnchantments());
        $availableBonuses = array_values(array_diff(array_keys($pool), $currentEnchants));
        
        if (empty($availableBonuses)) {
            $availableBonuses = array_keys($pool);
        }

        $bonusKey = $availableBonuses[array_rand($availableBonuses)];
        $range = $pool[$bonusKey];
        $value = $this->rng->int($range[0], $range[1]);

        return ['type' => $bonusKey, 'value' => $value];
    }
    
    public function generateMultipleRandomEnchantments(ItemInstance $item, int $count): array
    {
        $type = $item->template->type;
        $isWeapon = in_array($type, ['sword', 'staff', 'bow', 'weapon']);
        $pool = $isWeapon ? $this->weaponBonuses : $this->armorBonuses;
        
        $availableBonuses = array_keys($pool);
        $enchants = [];
        
        for ($i = 0; $i < $count; $i++) {
            if (empty($availableBonuses)) break;
            
            $keyIndex = array_rand($availableBonuses);
            $bonusKey = $availableBonuses[$keyIndex];
            unset($availableBonuses[$keyIndex]);
            // Re-index array so array_rand works properly
            $availableBonuses = array_values($availableBonuses);
            
            $range = $pool[$bonusKey];
            $value = $this->rng->int($range[0], $range[1]);
            $enchants[$bonusKey] = $value;
        }
        
        return $enchants;
    }
}
