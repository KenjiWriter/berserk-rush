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
        // Nowe afiksy (2026-07-29): szansa (%) na dołożenie otrucia/ogłuszenia przy
        // trafieniu (patrz Character::getEquipmentStats() i silniki walki -
        // EncounterService/PvPEncounterService/GuildWarService) oraz bonus obrażeń
        // wyłącznie przeciwko innym graczom (PvP Arena / Wojna Gildii - potwory w PvE
        // nie są "bohaterami", więc tam ten bonus się nie liczy).
        'poison_chance' => [1, 7],
        'stun_chance' => [1, 7],
        'strong_vs_hero' => [5, 20],
    ];

    private array $armorBonuses = [
        'hp_bonus' => [50, 350],
        'defense' => [5, 30],
        'dodge_chance' => [1, 5],
        'resist_demons' => [2, 10],
        'resist_undead' => [2, 10],
        'resist_animals' => [2, 10],
        'resist_orcs' => [2, 10],
        // Odporność (%) redukująca szansę przeciwnika na otrucie/ogłuszenie -
        // patrz komentarz w $weaponBonuses wyżej.
        'resist_poison' => [1, 7],
        'resist_stun' => [1, 7],
    ];

    // Biżuteria (naszyjnik/pierścień): jedyny wyjątek od zasady "przedmioty nie
    // dodają atrybutów" - zaklinanie może sporadycznie trafić w niewielki, płaski
    // bonus do jednego atrybutu (+1..+5), obok zwykłych bonusów obronnych/HP.
    private array $accessoryBonuses = [
        'hp_bonus' => [20, 120],
        'defense' => [2, 10],
        'crit_chance' => [1, 5],
        'str_bonus' => [1, 5],
        'agi_bonus' => [1, 5],
        'int_bonus' => [1, 5],
        'vit_bonus' => [1, 5],
        // Kolejny wyjątek biżuterii (2026-07-29): szansa (%) na podwojenie nagrody
        // ze zwycięskiej walki PvE - patrz EncounterService::calculateGoldReward()/
        // calculateXpReward() (x2 złota/expa) oraz DropService (x2 ilość dropu
        // materiałów/przedmiotów). Tylko naszyjnik/pierścień mogą je wylosować.
        'double_exp_chance' => [1, 10],
        'double_gold_chance' => [1, 10],
        'double_drop_chance' => [1, 10],
    ];

    // Etykiety bonusów używane w UI (np. lista "Możliwe Zaklęcia" u Wiedźmy) -
    // scentralizowane tutaj, żeby nowe afiksy nie wymagały aktualizacji kilku
    // niezależnych map w blade'ach.
    private const BONUS_LABELS = [
        'attack_power' => 'Obrażenia Fizyczne',
        'magic_attack' => 'Obrażenia Magiczne',
        'crit_chance' => 'Szansa na Trafienie Krytyczne',
        'strong_vs_demons' => 'Silny vs Demony',
        'strong_vs_undead' => 'Silny vs Nieumarli',
        'strong_vs_animals' => 'Silny vs Zwierzęta',
        'strong_vs_orcs' => 'Silny vs Orki',
        'strong_vs_hero' => 'Silny vs Bohaterów',
        'poison_chance' => 'Szansa na Otrucie',
        'stun_chance' => 'Szansa na Ogłuszenie',
        'hp_bonus' => 'Punkty Życia (HP)',
        'defense' => 'Obrona',
        'dodge_chance' => 'Szansa na Unik',
        'resist_demons' => 'Odporność na Demony',
        'resist_undead' => 'Odporność na Nieumarłe',
        'resist_animals' => 'Odporność na Zwierzęta',
        'resist_orcs' => 'Odporność na Orki',
        'resist_poison' => 'Odporność na Otrucie',
        'resist_stun' => 'Odporność na Ogłuszenie',
        'str_bonus' => 'Siła (STR)',
        'int_bonus' => 'Inteligencja (INT)',
        'vit_bonus' => 'Witalność (VIT)',
        'agi_bonus' => 'Zręczność (AGI)',
        'double_exp_chance' => 'Szansa na Podwójne EXP',
        'double_gold_chance' => 'Szansa na Podwójne Złoto',
        'double_drop_chance' => 'Szansa na Podwójny Łup',
    ];

    public static function bonusLabel(string $key): string
    {
        return self::BONUS_LABELS[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    public function __construct(private RandomProvider $rng)
    {}

    private function poolFor(ItemInstance $item): array
    {
        $type = $item->template->type;

        if ($type === 'accessory') {
            return $this->accessoryBonuses;
        }

        $isWeapon = in_array($type, ['sword', 'staff', 'bow', 'weapon']);
        return $isWeapon ? $this->weaponBonuses : $this->armorBonuses;
    }

    /**
     * Zwraca pełną pulę możliwych bonusów (klucz => [min, max]) dla danego typu
     * przedmiotu - używane w UI (lista dostępnych zaklęć u Wiedźmy), bez
     * ujawniania szczegółów doboru puli (poolFor() zostaje prywatne).
     */
    public function getPossibleBonuses(ItemInstance $item): array
    {
        return $this->poolFor($item);
    }

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

    // Afiksy o rozkładzie "rzadkim na górze" (2026-07-29, na życzenie użytkownika,
    // wzorem FMS/zatrutego miecza z Metin2): 'attack_power'/'magic_attack' są teraz
    // procentowym bonusem do obrażeń fizycznych/magicznych (patrz
    // Character::getEquipmentStats(), gdzie mnożą sumaryczne attack_min/max zamiast
    // dodawać do nich płaską wartość), a losowanie wartości w ich zakresie ([10,50])
    // NIE jest jednostajne jak reszta puli - wysoki wynik jest wykładniczo rzadszy,
    // żeby np. +30% było spotykane, a +45-50% było prawdziwym rarytasem.
    private const RARE_SCALING_KEYS = ['attack_power', 'magic_attack'];
    private const RARE_SCALING_SKEW = 3.0;

    private function rollBonusValue(string $bonusKey, array $range): int
    {
        if (!in_array($bonusKey, self::RARE_SCALING_KEYS, true)) {
            return $this->rng->int($range[0], $range[1]);
        }

        // roll^skew skupia wynik blisko 0 - np. przy skew=3 szansa na trafienie
        // górnych 20% zakresu (tu: 40-50%) to ok. 9%, a samego maksimum (50%) to
        // ułamek procenta - "prawie nieosiągalne, ale nie niemożliwe".
        $roll = $this->rng->float(0.0, 1.0);
        $skewed = $roll ** self::RARE_SCALING_SKEW;

        return $range[0] + (int) round($skewed * ($range[1] - $range[0]));
    }

    public function generateRandomEnchantment(ItemInstance $item): array
    {
        $pool = $this->poolFor($item);

        $currentEnchants = array_keys($item->getEnchantments());
        $availableBonuses = array_values(array_diff(array_keys($pool), $currentEnchants));

        if (empty($availableBonuses)) {
            $availableBonuses = array_keys($pool);
        }

        $bonusKey = $availableBonuses[array_rand($availableBonuses)];
        $range = $pool[$bonusKey];
        $value = $this->rollBonusValue($bonusKey, $range);

        return ['type' => $bonusKey, 'value' => $value];
    }

    public function generateMultipleRandomEnchantments(ItemInstance $item, int $count): array
    {
        $pool = $this->poolFor($item);

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
            $value = $this->rollBonusValue($bonusKey, $range);
            $enchants[$bonusKey] = $value;
        }

        return $enchants;
    }
}
