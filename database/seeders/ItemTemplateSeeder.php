<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;

class ItemTemplateSeeder extends Seeder
{
    /**
     * Konwertuje pojedynczą, dotychczas stałą wartość statu na przedział
     * [min, max] o wariancji +/-$variancePct wokół tej wartości - to z takiego
     * przedziału ItemStatRoller losuje konkretną wartość dla każdego dropu/wykucia/
     * zakupu (patrz app/Application/Items/ItemStatRoller.php). Tylko dla
     * weapon/armor/accessory - reszta typów (consumable/material) zostaje skalarna.
     */
    private function statRange(float $value, float $variancePct = 0.25, ?float $cap = null): array
    {
        // Minimum to zawsze co najmniej 1 - stat, który przedmiot faktycznie
        // posiada, nie może wylosować się jako "0" (czyli praktycznie brak statu).
        $lo = max(1, (int) floor($value * (1 - $variancePct)));
        $hi = (int) ceil($value * (1 + $variancePct));
        if ($cap !== null) {
            $hi = (int) min($cap, $hi);
        }
        if ($hi <= $lo) {
            $hi = $lo + 1;
        }

        return [$lo, $hi];
    }

    public function run(): void
    {
        $manualItems = [
            // Consumables
            ['id' => Str::ulid(), 'name' => 'Mikstura Leczenia', 'type' => 'consumable', 'slot' => null, 'level_requirement' => 1, 'base_stats' => ['heal_amount' => 25], 'description' => 'Czerwona mikstura.', 'icon' => 'bagienne-ziolo.png', 'rarity_weights' => ['common' => 80, 'uncommon' => 15, 'rare' => 5]],
            ['id' => Str::ulid(), 'name' => 'Eliksir Many', 'type' => 'consumable', 'slot' => null, 'level_requirement' => 2, 'base_stats' => ['mana_amount' => 30], 'description' => 'Niebieska mikstura.', 'icon' => 'czysta-mana.png', 'rarity_weights' => ['common' => 75, 'uncommon' => 20, 'rare' => 5]],
            ['id' => Str::ulid(), 'name' => 'Wielka Mikstura Leczenia', 'type' => 'consumable', 'slot' => null, 'level_requirement' => 5, 'base_stats' => ['heal_amount' => 150], 'description' => 'Potężna mikstura.', 'icon' => 'metna-woda.png', 'rarity_weights' => ['common' => 60, 'uncommon' => 30, 'rare' => 10]],

            // Consumable Scrolls
            ['id' => '01k4jpx94j70x2vv10b835scr1', 'name' => 'Zwój Resetu Umiejętności', 'type' => 'consumable', 'sub_type' => 'scroll', 'slot' => null, 'level_requirement' => 1, 'base_stats' => ['effect' => 'reset_skills'], 'description' => 'Pozwala zresetować umiejętności bojowe aktywnej postaci i odzyskać zainwestowane Punkty Umiejętności.', 'icon' => 'zwoj-resetu-umiejetnosci.png', 'rarity_weights' => ['common' => 0, 'uncommon' => 60, 'rare' => 40]],
            ['id' => '01k4jpx94j70x2vv10b835scr2', 'name' => 'Zwój Resetu Atrybutów', 'type' => 'consumable', 'sub_type' => 'scroll', 'slot' => null, 'level_requirement' => 1, 'base_stats' => ['effect' => 'reset_attributes'], 'description' => 'Pozwala zresetować przydzielone punkty atrybutów (STR, INT, VIT, AGI) aktywnej postaci i odzyskać Punkty Postaci.', 'icon' => 'zwoj-resetu-atrybutow.png', 'rarity_weights' => ['common' => 0, 'uncommon' => 60, 'rare' => 40]],
            ['id' => '01k4jpx94j70x2vv10b835scr3', 'name' => 'Zwój Pełnego Resetu', 'type' => 'consumable', 'sub_type' => 'scroll', 'slot' => null, 'level_requirement' => 1, 'base_stats' => ['effect' => 'reset_full'], 'description' => 'Pozwala zresetować zarówno atrybuty, jak i umiejętności bojowe aktywnej postaci za jednym razem.', 'icon' => 'zwoj-pelnego-resetu.png', 'rarity_weights' => ['common' => 0, 'uncommon' => 40, 'rare' => 60]],
            ['id' => '01k4jpx94j70x2vv10b835scr4', 'name' => 'Zwój Areny Walki', 'type' => 'consumable', 'sub_type' => 'scroll', 'slot' => null, 'level_requirement' => 1, 'base_stats' => ['effect' => 'arena_attempt'], 'description' => 'Przywraca 1 wykorzystaną próbę na Arenie Walk w danym dniu.', 'icon' => 'zwoj-areny-walki.png', 'rarity_weights' => ['common' => 0, 'uncommon' => 70, 'rare' => 30]],

            // Keys & Tutorial / Starter Equipment
            // UWAGA (bound_to_character -> tylko equip-state, 2026-07-29): te 3 przedmioty
            // (rozdawane raz na zawsze przez CreateCharacter/TutorialOverlay) mają
            // is_tradeable=false, żeby nie dało się ich nigdy sprzedać/wystawić/zdeponować,
            // niezależnie od tego, czy są aktualnie założone - bound_to_character od teraz
            // odzwierciedla WYŁĄCZNIE aktualny stan noszenia (patrz EquipItem/UnequipItem).
            ['id' => '01k4jpx94j70x2vv10b835key1', 'name' => 'Zardzewiały Klucz do Lochów', 'type' => 'material', 'sub_type' => null, 'slot' => null, 'level_requirement' => 8, 'base_stats' => [], 'description' => 'Tajemniczy stary klucz.', 'icon' => 'zardzewialy-klucz-do-lochow.png', 'rarity_weights' => ['common' => 0, 'uncommon' => 70, 'rare' => 30]],
            ['id' => '01k4jpx94j70x2vv10b835prm4', 'name' => 'Zardzewiały Miecz', 'type' => 'weapon', 'sub_type' => 'sword', 'slot' => 'main_hand', 'level_requirement' => 1, 'base_stats' => ['attack_min' => $this->statRange(2), 'attack_max' => $this->statRange(4), 'crit_chance' => $this->statRange(1), 'vit_bonus' => $this->statRange(5)], 'description' => 'Podstawowa broń.', 'icon' => 'zardzewialy-miecz.png', 'rarity_weights' => ['common' => 100], 'is_tradeable' => false],
            ['id' => '01k4jpx94j70x2vv10b835nov1', 'name' => 'Miecz Nowicjusza', 'type' => 'weapon', 'sub_type' => 'sword', 'slot' => 'main_hand', 'level_requirement' => 1, 'base_stats' => ['attack_min' => $this->statRange(2), 'attack_max' => $this->statRange(5), 'crit_chance' => $this->statRange(1)], 'description' => 'Solidny miecz treningowy dla nowicjuszy.', 'icon' => 'miecz-nowicjusza.png', 'rarity_weights' => ['common' => 100]],
            ['id' => '01k4jpx94j70x2vv10b835hlm1', 'name' => 'Zardzewiały Hełm', 'type' => 'armor', 'sub_type' => null, 'slot' => 'head', 'level_requirement' => 1, 'base_stats' => ['defense' => $this->statRange(2), 'hp_bonus' => $this->statRange(8)], 'description' => 'Podstawowy hełm ochronny podarowany przez Kapitana.', 'icon' => 'helm-rekruta.png', 'rarity_weights' => ['common' => 100], 'is_tradeable' => false],
            ['id' => '01k4jpx94j70x2vv10b835arm1', 'name' => 'Skórzana Zbroja', 'type' => 'armor', 'sub_type' => null, 'slot' => 'chest', 'level_requirement' => 1, 'base_stats' => ['defense' => $this->statRange(4), 'hp_bonus' => $this->statRange(14)], 'description' => 'Solidna zbroja podarowana przez Kapitana na koniec wstępnego treningu.', 'icon' => 'zbroja-rekruta.png', 'rarity_weights' => ['common' => 100], 'is_tradeable' => false],
        ];

        foreach ($manualItems as $item) {
            $existing = null;
            if (isset($item['id'])) {
                $existing = ItemTemplate::find($item['id']);
            }
            if (!$existing) {
                $existing = ItemTemplate::where('name', $item['name'])->first();
            }

            if ($existing) {
                unset($item['id']);
                $existing->update($item);
            } else {
                ItemTemplate::create($item);
            }
        }



        // UWAGA (itemizacja klasowa, 2026-07-28): bronie NADAL nie przydzielają surowych
        // atrybutów (STR/INT/VIT/AGI) - dają wyłącznie obrażenia fizyczne/magiczne
        // (attack_min/max, magic_attack_min/max lub "magic burst" Dzwonów) i crit_chance,
        // dokładnie jak w poprzednim reworku itemizacji. To, co się zmieniło, to 3 zestawy
        // zbroi (hełm/klatka/buty) tematyczne per "klasa" (patrz $prototypes niżej:
        // sufiks `_w` = Wojownik, `_m` = Mag, `_a` = Skrytobójca/Ninja) - te NAPRAWDĘ znowu
        // dają surowe atrybuty, oprócz swoich zwykłych statów (defense/hp_bonus/mana_bonus/
        // crit_chance), poprzez tablicę $classArmorAttributes zdefiniowaną niżej:
        //   - Mag (`helmet_m`/`armor_m`/`boots_m`)         -> INT (int_bonus)
        //   - Skrytobójca/Ninja (`helmet_a`/`armor_a`/`boots_a`) -> AGI (agi_bonus)
        //   - Wojownik (`helmet_w`/`armor_w`/`boots_w`)     -> STR + VIT (str_bonus/vit_bonus, 50/50)
        //
        // Balans (patrz też docs/modules/profile_and_equipment.md, sekcja 4):
        //   - Pula ręcznych punktów postaci (`character_points`) na max poziomie (99) to
        //     10 + 98*3 = 304 (formuła w `LevelUpService`/`Character::syncMissingPoints`).
        //   - Najlepszy zestaw zbroi możliwy do założenia na poziomie 90 to tier `level=85`
        //     (kolejny, `level=95`, wymaga wyższego levelu) - ten tier celowo sumuje się do
        //     RÓWNO 200 pkt danego atrybutu (head 25% / chest 50% / boots 25% z 200), czyli
        //     ok. 40% w stosunku do pełnej ręcznej puli (200 / (200+304) ≈ 39,7%) - realnie
        //     mniej, bo na poziomie 90 gracz ma dopiero 277 pkt ręcznych, nie 304.
        //   - Progresja jest CELOWO liniowa (nie eksploduje wykładniczo jak `scale` przy
        //     obrażeniach) - rośnie od 12 pkt (tier lvl 5) do 233 pkt (tier lvl 99, najlepszy
        //     w grze), żeby ręczne rozdawanie punktów zawsze pozostawało głównym (>55%)
        //     źródłem atrybutów, a eq klasowe było wyraźnym, ale nie dominującym bonusem.
        //   - Ulepszanie (+0..+9 w Kowalu) i tak dolicza swoje standardowe +10%/poziom do
        //     KAŻDEGO dodatniego stata w `base_stats` (patrz `ItemInstance::getUpgradeBonusStats()`),
        //     więc w pełni wykute +9 sztuki dają dodatkowo do +90% ponad wartości z tabeli -
        //     to świadomie spójne z tym, jak działają już inne staty (obrażenia/obrona/hp).
        //   - Jedynym wyjątkiem POZA tym systemem klasowym pozostaje biżuteria (naszyjnik/
        //     pierścień), która sporadycznie i w niewielkiej, płaskiej ilości (+1..+5, patrz
        //     niżej w pętli generującej) może dać bonus do LOSOWEGO atrybutu, niezależnie od
        //     klasy/tematu.
        $classArmorAttributes = [
            5   => [
                'helmet_w' => ['str_bonus' => 2, 'vit_bonus' => 1], 'armor_w' => ['str_bonus' => 3, 'vit_bonus' => 3], 'boots_w' => ['str_bonus' => 2, 'vit_bonus' => 1],
                'helmet_m' => ['int_bonus' => 3], 'armor_m' => ['int_bonus' => 6], 'boots_m' => ['int_bonus' => 3],
                'helmet_a' => ['agi_bonus' => 3], 'armor_a' => ['agi_bonus' => 6], 'boots_a' => ['agi_bonus' => 3],
            ],
            15  => [
                'helmet_w' => ['str_bonus' => 4, 'vit_bonus' => 5], 'armor_w' => ['str_bonus' => 8, 'vit_bonus' => 9], 'boots_w' => ['str_bonus' => 4, 'vit_bonus' => 5],
                'helmet_m' => ['int_bonus' => 9], 'armor_m' => ['int_bonus' => 17], 'boots_m' => ['int_bonus' => 9],
                'helmet_a' => ['agi_bonus' => 9], 'armor_a' => ['agi_bonus' => 17], 'boots_a' => ['agi_bonus' => 9],
            ],
            25  => [
                'helmet_w' => ['str_bonus' => 8, 'vit_bonus' => 7], 'armor_w' => ['str_bonus' => 14, 'vit_bonus' => 15], 'boots_w' => ['str_bonus' => 8, 'vit_bonus' => 7],
                'helmet_m' => ['int_bonus' => 15], 'armor_m' => ['int_bonus' => 29], 'boots_m' => ['int_bonus' => 15],
                'helmet_a' => ['agi_bonus' => 15], 'armor_a' => ['agi_bonus' => 29], 'boots_a' => ['agi_bonus' => 15],
            ],
            35  => [
                'helmet_w' => ['str_bonus' => 10, 'vit_bonus' => 11], 'armor_w' => ['str_bonus' => 20, 'vit_bonus' => 20], 'boots_w' => ['str_bonus' => 10, 'vit_bonus' => 11],
                'helmet_m' => ['int_bonus' => 21], 'armor_m' => ['int_bonus' => 40], 'boots_m' => ['int_bonus' => 21],
                'helmet_a' => ['agi_bonus' => 21], 'armor_a' => ['agi_bonus' => 40], 'boots_a' => ['agi_bonus' => 21],
            ],
            45  => [
                'helmet_w' => ['str_bonus' => 13, 'vit_bonus' => 13], 'armor_w' => ['str_bonus' => 27, 'vit_bonus' => 27], 'boots_w' => ['str_bonus' => 13, 'vit_bonus' => 13],
                'helmet_m' => ['int_bonus' => 26], 'armor_m' => ['int_bonus' => 54], 'boots_m' => ['int_bonus' => 26],
                'helmet_a' => ['agi_bonus' => 26], 'armor_a' => ['agi_bonus' => 54], 'boots_a' => ['agi_bonus' => 26],
            ],
            55  => [
                'helmet_w' => ['str_bonus' => 16, 'vit_bonus' => 16], 'armor_w' => ['str_bonus' => 32, 'vit_bonus' => 33], 'boots_w' => ['str_bonus' => 16, 'vit_bonus' => 16],
                'helmet_m' => ['int_bonus' => 32], 'armor_m' => ['int_bonus' => 65], 'boots_m' => ['int_bonus' => 32],
                'helmet_a' => ['agi_bonus' => 32], 'armor_a' => ['agi_bonus' => 65], 'boots_a' => ['agi_bonus' => 32],
            ],
            65  => [
                'helmet_w' => ['str_bonus' => 19, 'vit_bonus' => 19], 'armor_w' => ['str_bonus' => 38, 'vit_bonus' => 39], 'boots_w' => ['str_bonus' => 19, 'vit_bonus' => 19],
                'helmet_m' => ['int_bonus' => 38], 'armor_m' => ['int_bonus' => 77], 'boots_m' => ['int_bonus' => 38],
                'helmet_a' => ['agi_bonus' => 38], 'armor_a' => ['agi_bonus' => 77], 'boots_a' => ['agi_bonus' => 38],
            ],
            75  => [
                'helmet_w' => ['str_bonus' => 22, 'vit_bonus' => 22], 'armor_w' => ['str_bonus' => 44, 'vit_bonus' => 44], 'boots_w' => ['str_bonus' => 22, 'vit_bonus' => 22],
                'helmet_m' => ['int_bonus' => 44], 'armor_m' => ['int_bonus' => 88], 'boots_m' => ['int_bonus' => 44],
                'helmet_a' => ['agi_bonus' => 44], 'armor_a' => ['agi_bonus' => 88], 'boots_a' => ['agi_bonus' => 44],
            ],
            85  => [
                'helmet_w' => ['str_bonus' => 25, 'vit_bonus' => 25], 'armor_w' => ['str_bonus' => 50, 'vit_bonus' => 50], 'boots_w' => ['str_bonus' => 25, 'vit_bonus' => 25],
                'helmet_m' => ['int_bonus' => 50], 'armor_m' => ['int_bonus' => 100], 'boots_m' => ['int_bonus' => 50],
                'helmet_a' => ['agi_bonus' => 50], 'armor_a' => ['agi_bonus' => 100], 'boots_a' => ['agi_bonus' => 50],
            ],
            95  => [
                'helmet_w' => ['str_bonus' => 28, 'vit_bonus' => 28], 'armor_w' => ['str_bonus' => 56, 'vit_bonus' => 56], 'boots_w' => ['str_bonus' => 28, 'vit_bonus' => 28],
                'helmet_m' => ['int_bonus' => 56], 'armor_m' => ['int_bonus' => 112], 'boots_m' => ['int_bonus' => 56],
                'helmet_a' => ['agi_bonus' => 56], 'armor_a' => ['agi_bonus' => 112], 'boots_a' => ['agi_bonus' => 56],
            ],
            99  => [
                'helmet_w' => ['str_bonus' => 29, 'vit_bonus' => 29], 'armor_w' => ['str_bonus' => 58, 'vit_bonus' => 59], 'boots_w' => ['str_bonus' => 29, 'vit_bonus' => 29],
                'helmet_m' => ['int_bonus' => 58], 'armor_m' => ['int_bonus' => 117], 'boots_m' => ['int_bonus' => 58],
                'helmet_a' => ['agi_bonus' => 58], 'armor_a' => ['agi_bonus' => 117], 'boots_a' => ['agi_bonus' => 58],
            ],
        ];

        // UWAGA (rebalans obrażeń/HP, 2026-07-28): attack_min/max, magic_attack_min/max,
        // magic_burst_min/max i hp_bonus obcięte o 25% (x0.75) względem poprzednich
        // wartości - zrekompensowane podniesieniem mnożnika atrybutów w formule obrażeń
        // (`Character::ATTRIBUTE_DAMAGE_MULTIPLIER`, x1.5) i HP (`ATTRIBUTE_HP_MULTIPLIER`,
        // VIT x15 zamiast x10). `defense` i `crit_chance`/`magic_burst_chance` (szanse, nie
        // wartości obrażeń) NIE są tu ruszane - poza zakresem tej zmiany. Wartości niecałkowite
        // są celowe: finalna wartość per-tier i tak przechodzi przez `(int) round(... * scale)`
        // niżej w pętli generującej.
        $prototypes = [
            'sword'    => ['type' => 'weapon', 'sub_type' => 'sword', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 1, 'double_strike_chance' => 20]],
            'axe'      => ['type' => 'weapon', 'sub_type' => 'axe', 'slot' => 'main_hand', 'stats' => ['attack_min' => 0.75, 'attack_max' => 7.5, 'bleed_chance' => 25]],
            'bow'      => ['type' => 'weapon', 'sub_type' => 'bow', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 3, 'armor_pen_pct' => 20]],
            // Dzwon (bell): broń hybrydowa - normalny atak fizyczny jak inne bronie
            // walki wręcz, plus szansa na dodatkowe, OSOBNE obrażenia magiczne przy
            // trafieniu ("magic burst"). Patrz EncounterService::calculateDamage().
            'bell'     => ['type' => 'weapon', 'sub_type' => 'bell', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.8, 'attack_max' => 3.6, 'magic_burst_chance' => 30, 'magic_burst_min' => 3, 'magic_burst_max' => 6.75]],
            'wand'     => ['type' => 'weapon', 'sub_type' => 'wand', 'slot' => 'main_hand', 'stats' => ['magic_attack_min' => 3, 'magic_attack_max' => 6.75, 'crit_chance' => 1, 'magic_infusion_chance' => 25]],
            // UWAGA (rebalans dagger/bell, 2026-07-29): patrz identyczna notatka w
            // ShopEquipmentSeeder.php - attack_min/max podniesione, bo przy oryginalnych
            // wartościach obie te bronie systematycznie nie spełniały celu >=90% winrate
            // (symulacja: php artisan balance:monsters), niezależnie od tego jak
            // dobierano staty potworów.
            'dagger'   => ['type' => 'weapon', 'sub_type' => 'dagger', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.2, 'attack_max' => 4.8, 'crit_chance' => 4, 'poison_chance' => 25]],

            'helmet_w' => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 3, 'hp_bonus' => 10.5]],
            'armor_w'  => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 6, 'hp_bonus' => 19.5]],
            'boots_w'  => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 2, 'hp_bonus' => 6]],

            'helmet_m' => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 1, 'hp_bonus' => 4.5, 'mana_bonus' => 15]],
            'armor_m'  => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 2, 'hp_bonus' => 7.5, 'mana_bonus' => 30]],
            'boots_m'  => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 1, 'hp_bonus' => 3.75, 'mana_bonus' => 10]],

            'helmet_a' => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 2, 'hp_bonus' => 4.5, 'crit_chance' => 1]],
            'armor_a'  => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 3, 'hp_bonus' => 7.5, 'crit_chance' => 2]],
            'boots_a'  => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 2, 'hp_bonus' => 4.5, 'crit_chance' => 1]],

            'amulet'   => ['type' => 'accessory', 'slot' => 'neck', 'stats' => ['hp_bonus' => 15, 'defense' => 1, 'crit_chance' => 1]],
            'ring'     => ['type' => 'accessory', 'slot' => 'ring', 'stats' => ['hp_bonus' => 7.5, 'defense' => 1, 'crit_chance' => 1]],
        ];

        $themes = [
            [
                'level' => 5, 'scale' => 2,
                'names' => [
                    'sword' => 'Miecz Leśnego Goblina', 'axe' => 'Topór Drwala z Mrocznego Lasu', 'bow' => 'Łuk z Pnia Suchodrzewu',
                    'bell' => 'Dzwon Leśnego Szamana', 'wand' => 'Różdżka z Cisa', 'dagger' => 'Sztylety z Kości Wilka',
                    'helmet_w' => 'Hełm Leśnego Strażnika', 'armor_w' => 'Pancerz z Wilczej Skóry', 'boots_w' => 'Buty Tropiącego',
                    'helmet_m' => 'Kaptur Spleciony z Mchu', 'armor_m' => 'Szata Leśnego Ducha', 'boots_m' => 'Miękkie Mokasyny',
                    'helmet_a' => 'Maska z Czaszki Wilka', 'armor_a' => 'Skórznia Nocnego Łowcy', 'boots_a' => 'Ciche Podeszwy',
                    'amulet' => 'Naszyjnik z Kłów', 'ring' => 'Pierścień Wędrowca'
                ]
            ],
            [
                'level' => 15, 'scale' => 6,
                'names' => [
                    'sword' => 'Ostrze Króla Lasu', 'axe' => 'Ciężki Topór Enta', 'bow' => 'Łuk Nocnego Myśliwego',
                    'bell' => 'Dzwon Prastarych Drzew', 'wand' => 'Kostur z Serca Suchodrzewu', 'dagger' => 'Zatrute Sztylety Goblina',
                    'helmet_w' => 'Wzmocniony Hełm Strażnika', 'armor_w' => 'Zbroja z Twardej Kory', 'boots_w' => 'Okute Buty Leśnika',
                    'helmet_m' => 'Kaptur Krwawego Mchu', 'armor_m' => 'Szata Spaczonego Lasu', 'boots_m' => 'Trzewiki Korzeni',
                    'helmet_a' => 'Maska Półcienia', 'armor_a' => 'Płaszcz Liściastego Skrytobójcy', 'boots_a' => 'Buty Skoku',
                    'amulet' => 'Amulet Prastarego Dębu', 'ring' => 'Pierścień Splecionych Korzeni'
                ]
            ],
            [
                'level' => 25, 'scale' => 15,
                'names' => [
                    'sword' => 'Zardzewiały Miecz Szkieletu', 'axe' => 'Ząbkowany Topór Upiora', 'bow' => 'Łuk z Kości Zjaw',
                    'bell' => 'Dzwon Pokutny', 'wand' => 'Różdżka Potępionych Dusz', 'dagger' => 'Sztylety Skrytobójcy Dusz',
                    'helmet_w' => 'Zardzewiały Hełm Rycerza', 'armor_w' => 'Kolczuga Strażnika Ruin', 'boots_w' => 'Żelazne Sabatony',
                    'helmet_m' => 'Kaptur Zjaw', 'armor_m' => 'Zbutwiała Szata Licza', 'boots_m' => 'Buty Mgły',
                    'helmet_a' => 'Maska Beztwarzowego Ducha', 'armor_a' => 'Skórznia Z Grobowca', 'boots_a' => 'Cmentarne Buty',
                    'amulet' => 'Naszyjnik z Zimnej Stali', 'ring' => 'Pierścień Wiecznego Żalu'
                ]
            ],
            [
                'level' => 35, 'scale' => 35,
                'names' => [
                    'sword' => 'Maczuga Ogra', 'axe' => 'Rozłupywacz Czaszek', 'bow' => 'Łuk z Kości Jaskiniowca',
                    'bell' => 'Dzwon Szamana Trolli', 'wand' => 'Różdżka Ziemnej Magii', 'dagger' => 'Sztylety z Zębów Nietoperza',
                    'helmet_w' => 'Hełm z Czaszki Ogra', 'armor_w' => 'Gruboskórny Pancerz Trolla', 'boots_w' => 'Masywne Buciska',
                    'helmet_m' => 'Szamański Kaptur Trolli', 'armor_m' => 'Szata z Futer Nietoperzy', 'boots_m' => 'Buty z Mchu Jaskiniowego',
                    'helmet_a' => 'Maska Łowcy Ogrów', 'armor_a' => 'Płaszcz Skalnego Cienia', 'boots_a' => 'Buty Cichego Kroku',
                    'amulet' => 'Amulet Skalnego Trolla', 'ring' => 'Kamienny Pierścień'
                ]
            ],
            [
                'level' => 45, 'scale' => 80,
                'names' => [
                    'sword' => 'Glewia Wodza Orków', 'axe' => 'Topór Berserkera Orków', 'bow' => 'Łuk Krwawego Zwiadu',
                    'bell' => 'Dzwon Krwawego Rytuału', 'wand' => 'Kostur Szamana Krwi', 'dagger' => 'Sztylety Pustkowi',
                    'helmet_w' => 'Hełm Wodza Orków', 'armor_w' => 'Pancerz z Hartowanej Stali', 'boots_w' => 'Buty Orkowego Wojownika',
                    'helmet_m' => 'Kaptur Szamana Krwi', 'armor_m' => 'Szata Nasączona Krwią', 'boots_m' => 'Trzewiki Rytualne',
                    'helmet_a' => 'Maska Pustynnego Wiatru', 'armor_a' => 'Skórznia Orkowego Zabójcy', 'boots_a' => 'Buty Burzy Piaskowej',
                    'amulet' => 'Naszyjnik Orkowego Wodza', 'ring' => 'Pierścień Berserkera'
                ]
            ],
            [
                'level' => 55, 'scale' => 180,
                'names' => [
                    'sword' => 'Ostrze z Zęba Hydry', 'axe' => 'Zbutwiały Topór Topielca', 'bow' => 'Łuk z Wierzby Płaczącej',
                    'bell' => 'Dzwon Utopców', 'wand' => 'Różdżka Wiedźmiej Straży', 'dagger' => 'Zatrute Kły Hydry',
                    'helmet_w' => 'Zardzewiały Hełm z Głębin', 'armor_w' => 'Pancerz z Łusek Hydry', 'boots_w' => 'Mokre Buty Bagienne',
                    'helmet_m' => 'Kaptur Wiedźmy Zgnilizny', 'armor_m' => 'Szata Tkana z Zielska', 'boots_m' => 'Buty Bagiennej Mgły',
                    'helmet_a' => 'Maska z Błota', 'armor_a' => 'Skórznia Żmijowa', 'boots_a' => 'Podeszwy Bezdźwięku',
                    'amulet' => 'Naszyjnik z Oka Hydry', 'ring' => 'Pierścień Zgniłego Mchu'
                ]
            ],
            [
                'level' => 65, 'scale' => 400,
                'names' => [
                    'sword' => 'Miecz Wykuty z Bazaltu', 'axe' => 'Topór Kamiennego Golema', 'bow' => 'Łuk z Piór Harpii',
                    'bell' => 'Dzwon Górskiego Echa', 'wand' => 'Różdżka z Górskiego Kryształu', 'dagger' => 'Sztylety Skalnego Kła',
                    'helmet_w' => 'Hełm z Czarnego Bazaltu', 'armor_w' => 'Pancerz Skalnego Golema', 'boots_w' => 'Ciężkie Kamienne Buty',
                    'helmet_m' => 'Kaptur Burzowych Chmur', 'armor_m' => 'Szata z Piór Harpii', 'boots_m' => 'Trzewiki Górskiego Wiatru',
                    'helmet_a' => 'Maska Nocnego Drapieżnika', 'armor_a' => 'Płaszcz Górskiego Cienia', 'boots_a' => 'Buty Sokolnika',
                    'amulet' => 'Naszyjnik ze Szponu Harpii', 'ring' => 'Pierścień Czarnego Kryształu'
                ]
            ],
            [
                'level' => 75, 'scale' => 1000,
                'names' => [
                    'sword' => 'Piekielny Miecz Smoka', 'axe' => 'Topór Smoczego Gniewu', 'bow' => 'Smoczy Łuk',
                    'bell' => 'Dzwon Oddechu Smoka', 'wand' => 'Różdżka Smoczej Łuski', 'dagger' => 'Sztylety z Cienia Smoka',
                    'helmet_w' => 'Hełm Smoczej Straży', 'armor_w' => 'Pancerz ze Smoczych Łusek', 'boots_w' => 'Sabatony Smoka',
                    'helmet_m' => 'Kaptur Cienia Smoka', 'armor_m' => 'Szata Smoczego Ognia', 'boots_m' => 'Buty z Popiołu',
                    'helmet_a' => 'Maska Mrocznego Zabójcy', 'armor_a' => 'Skórznia Łowcy Smoków', 'boots_a' => 'Podeszwy Smoczego Lotu',
                    'amulet' => 'Amulet Smoczego Oka', 'ring' => 'Pierścień Władcy Cienia'
                ]
            ],
            [
                'level' => 85, 'scale' => 3000,
                'names' => [
                    'sword' => 'Miecz Runicznego Gwardzisty', 'axe' => 'Topór Magicznego Płomienia', 'bow' => 'Łuk z Eterycznej Energii',
                    'bell' => 'Dzwon Mistrza Iluzji', 'wand' => 'Kostur Arcymaga', 'dagger' => 'Sztylety z Czystej Energii',
                    'helmet_w' => 'Hełm Strażnika Arkanów', 'armor_w' => 'Zbroja Runiczna', 'boots_w' => 'Buty Żywiołaka Płomieni',
                    'helmet_m' => 'Kaptur Arcymaga', 'armor_m' => 'Szata Mistrza Iluzji', 'boots_m' => 'Buty Lewitacji',
                    'helmet_a' => 'Maska Niewidzialności', 'armor_a' => 'Skórznia Nasączona Magią', 'boots_a' => 'Podeszwy z Eteru',
                    'amulet' => 'Naszyjnik Runicznej Energii', 'ring' => 'Pierścień Absolutu'
                ]
            ],
            [
                'level' => 95, 'scale' => 7000,
                'names' => [
                    'sword' => 'Ostrze Skażonego Rycerza', 'axe' => 'Topór Czarownicy Zgnilizny', 'bow' => 'Łuk Tkany z Pajęczyny Plagi',
                    'bell' => 'Dzwon Ostatniego Tchnienia', 'wand' => 'Różdżka Zmutowanego Czarownika', 'dagger' => 'Sztylety Jadu Pająka Plagi',
                    'helmet_w' => 'Hełm Rycerza Skazy', 'armor_w' => 'Pancerz Skażonej Stali', 'boots_w' => 'Buty Zgnilizny',
                    'helmet_m' => 'Kaptur Pająka Plagi', 'armor_m' => 'Szata z Przeklętego Jedwabiu', 'boots_m' => 'Buty Kwasu',
                    'helmet_a' => 'Maska Cienia Skazy', 'armor_a' => 'Skórznia Upadłego Zabójcy', 'boots_a' => 'Podeszwy Trucizny',
                    'amulet' => 'Amulet Zmutowanego Oka', 'ring' => 'Pierścień Zgnilizny'
                ]
            ],
            [
                'level' => 99, 'scale' => 15000,
                'names' => [
                    'sword' => 'Miecz Pana Zniszczenia', 'axe' => 'Rozdzieracz Światów', 'bow' => 'Łuk Apokalipsy',
                    'bell' => 'Dzwon Sądu Ostatecznego', 'wand' => 'Kostur Władcy Mroku', 'dagger' => 'Sztylety Ostatecznego Zniszczenia',
                    'helmet_w' => 'Korona Pana Zniszczenia', 'armor_w' => 'Pancerz Absolutnego Chaosu', 'boots_w' => 'Buty Deptania Światów',
                    'helmet_m' => 'Kaptur Pożeracza Dusz', 'armor_m' => 'Szata Mrocznej Pustki', 'boots_m' => 'Buty Otchłani',
                    'helmet_a' => 'Maska Bezwzględnego Zniszczenia', 'armor_a' => 'Płaszcz Końca Czasu', 'boots_a' => 'Ciche Podeszwy Zmierzchu',
                    'amulet' => 'Serce Pana Zniszczenia', 'ring' => 'Sygnet Apokalipsy'
                ]
            ],
        ];

        $generatedCount = 0;

        foreach ($themes as $index => $theme) {
            foreach ($prototypes as $protoKey => $proto) {
                
                // UWAGA (itemizacja przedziałowa): każdy stat ekwipunku (weapon/armor/
                // accessory - jedyne typy w $prototypes) to teraz przedział [min,max]
                // zamiast stałej liczby - patrz statRange() powyżej. ItemStatRoller losuje
                // konkretną wartość z tego przedziału przy dropie/wykuciu/zakupie.
                $scaledStats = [];
                foreach ($proto['stats'] as $statName => $baseValue) {
                    if (in_array($statName, ['crit_chance', 'magic_burst_chance', 'poison_chance', 'bleed_chance', 'double_strike_chance', 'armor_pen_pct', 'magic_infusion_chance'], true)) {
                        // Wartości procentowe - rosną powoli z kolejnym tier'em i mają cap 15% dla krytyka.
                        $cap = $statName === 'crit_chance' ? 15 : 70;
                        $critVal = min($cap, $baseValue + ($index * 0.5));

                        if ($statName === 'crit_chance') {
                            $isWeaponAbove15 = (($proto['type'] ?? '') === 'weapon' && ($theme['level'] ?? 0) > 15);
                            $isRingOrNecklace = in_array($protoKey, ['ring', 'amulet'], true) || in_array($proto['slot'] ?? '', ['ring', 'neck'], true);

                            if ($isWeaponAbove15 || $isRingOrNecklace) {
                                $critVal = $critVal * 0.5;
                            }
                        }

                        $scaledStats[$statName] = $this->statRange($critVal, 0.25, $cap);
                    } else {
                        $scaledStats[$statName] = $this->statRange($baseValue * $theme['scale']);
                    }
                }

                // Itemizacja klasowa: zestawy zbroi Wojownika/Maga/Skrytobójcy (_w/_m/_a)
                // dostają dodatkowo skalujący się liniowo bonus atrybutu, NIEZALEŻNIE od
                // multiplikatywnej skali `scale` powyżej (patrz duży komentarz nad
                // $classArmorAttributes). To jedyne miejsce (poza biżuterią) gdzie
                // wygenerowany szablon dostaje surowy atrybut.
                if (isset($classArmorAttributes[$theme['level']][$protoKey])) {
                    foreach ($classArmorAttributes[$theme['level']][$protoKey] as $attrKey => $attrVal) {
                        $scaledStats[$attrKey] = $this->statRange($attrVal);
                    }
                }

                // Wyjątek dla biżuterii: sporadyczny, PŁASKI bonus do jednego atrybutu
                // (+1..+5), niezależny od skali poziomu - to nadal osobny, dodatkowy
                // mechanizm (biżuteria nie ma "klasy") obok systemu klasowego wyżej.
                if (in_array($protoKey, ['amulet', 'ring'], true) && $index % 2 === 1) {
                    $flatAttrKeys = ['str_bonus', 'agi_bonus', 'int_bonus', 'vit_bonus'];
                    $flatValues = [1, 4, 5];
                    $scaledStats[$flatAttrKeys[$index % count($flatAttrKeys)]] = $this->statRange($flatValues[$index % count($flatValues)]);
                }

                $name = $theme['names'][$protoKey] ?? ('Przedmiot ' . $protoKey);

                $existing = ItemTemplate::where('name', $name)->first();
                if (!$existing) {
                    ItemTemplate::create([
                        'id' => Str::ulid(),
                        'name' => $name,
                        'type' => $proto['type'],
                        'sub_type' => $proto['sub_type'] ?? null,
                        'slot' => $proto['slot'],
                        'level_requirement' => $theme['level'],
                        'base_stats' => $scaledStats,
                        'description' => "Potężny artefakt odpowiedni dla poziomu " . $theme['level'] . ".",
                        'icon' => Str::slug($name) . '.png',
                        'rarity_weights' => [
                            'common' => 50,
                            'uncommon' => 30,
                            'rare' => 15,
                            'epic' => 4,
                            'legendary' => 1
                        ],
                    ]);
                } else {
                    // Re-uruchomienie seedera synchronizuje też statystyki z aktualnym
                    // schematem itemizacji klasowej (bez tego istniejące szablony
                    // zostałyby ze starymi statami na żywej bazie).
                    $existing->update([
                        'sub_type' => $proto['sub_type'] ?? null,
                        'base_stats' => $scaledStats,
                    ]);
                }

                $generatedCount++;
            }
        }

        $this->command->info('Created ' . count($manualItems) . ' manual items and ' . $generatedCount . ' generated item templates (total: ' . (count($manualItems) + $generatedCount) . ').');
    }
}

