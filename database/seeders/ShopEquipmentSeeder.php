<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MerchantItem;
use Illuminate\Support\Str;

class ShopEquipmentSeeder extends Seeder
{
    /**
     * Patrz ItemTemplateSeeder::statRange() - identyczna konwersja stałej wartości
     * na przedział [min,max] o wariancji +/-$variancePct, z którego ItemStatRoller
     * losuje konkretną wartość przy zakupie ekwipunku u kupca.
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
        // Usunięcie starych przedmiotów kupców
        MerchantItem::query()->whereIn('merchant_id', ['armorsmith', 'weaponsmith', 'merchant', 'gladiator'])->delete();

        // UWAGA (itemizacja klasowa, 2026-07-28): przedmioty ze sklepu (uniwersalne,
        // bez podziału na klasy Wojownik/Mag/Skrytobójca) NADAL nie dają surowych
        // atrybutów (STR/INT/VIT/AGI) - tylko obrażenia fizyczne, obrażenia magiczne,
        // obronę, HP i szansę na trafienie krytyczne. Dzwon (bell) to broń hybrydowa:
        // fizyczny atak bazowy + szansa na dodatkowy "magic burst". Zestawy TEMATYCZNE
        // per klasa (Wojownik/Mag/Skrytobójca -> STR+VIT/INT/AGI) istnieją tylko wśród
        // przedmiotów z dropu/craftingu w `ItemTemplateSeeder.php` (sufiksy `_w`/`_m`/`_a`)
        // - jeśli w przyszłości sklep też ma dostać zestawy klasowe, trzeba by potroić
        // liczbę wariantów `armor`/`helmet`/`boots` per tier tak jak tam.
        //
        // UWAGA (rebalans obrażeń/HP, 2026-07-28): attack_min/max, magic_attack_min/max,
        // magic_burst_min/max i hp_bonus obcięte o 25% (x0.75), zrekompensowane wyższym
        // mnożnikiem atrybutów w formule obrażeń/HP - patrz identyczna notatka i
        // wyjaśnienie w `ItemTemplateSeeder.php`.
        $prototypes = [
            'sword'   => ['type' => 'weapon', 'sub_type' => 'sword', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 1, 'double_strike_chance' => 10]],
            'axe'     => ['type' => 'weapon', 'sub_type' => 'axe', 'slot' => 'main_hand', 'stats' => ['attack_min' => 0.75, 'attack_max' => 6.75, 'bleed_chance' => 15]],
            'bow'     => ['type' => 'weapon', 'sub_type' => 'bow', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.5, 'attack_max' => 3.75, 'crit_chance' => 2, 'armor_pen_pct' => 12]],
            'wand'    => ['type' => 'weapon', 'sub_type' => 'wand', 'slot' => 'main_hand', 'stats' => ['magic_attack_min' => 2.25, 'magic_attack_max' => 5.25, 'crit_chance' => 1, 'magic_infusion_chance' => 12]],
            'dagger'  => ['type' => 'weapon', 'sub_type' => 'dagger', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.2, 'attack_max' => 4.8, 'crit_chance' => 3, 'poison_chance' => 15]],
            'bell'    => ['type' => 'weapon', 'sub_type' => 'bell', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1.5, 'attack_max' => 3.5, 'magic_burst_chance' => 15, 'magic_burst_min' => 2.25, 'magic_burst_max' => 4.5]],

            'armor'   => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 4, 'hp_bonus' => 13.5]],
            'helmet'  => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 2, 'hp_bonus' => 7.5]],
            'boots'   => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 1, 'hp_bonus' => 4.5]],
            'amulet'  => ['type' => 'accessory', 'slot' => 'neck', 'stats' => ['hp_bonus' => 11.25, 'mana_bonus' => 10, 'defense' => 1]],
            'ring'    => ['type' => 'accessory', 'slot' => 'ring', 'stats' => ['crit_chance' => 1, 'hp_bonus' => 6]],
        ];

        // Skala jest niższa niż w craftingu (~80%) -> teraz zmieniona na 1.2 aby Miecz Nowicjusza był lepszy od Zardzewiałego
        $themes = [
            [
                'level' => 1, 'scale' => 1.2,
                'names' => [
                    'sword' => 'Miecz Nowicjusza', 'axe' => 'Topór Nowicjusza', 'bow' => 'Łuk Nowicjusza',
                    'wand' => 'Różdżka Nowicjusza', 'dagger' => 'Sztylet Nowicjusza', 'bell' => 'Dzwon Nowicjusza',
                    'armor' => 'Zbroja Rekruta', 'helmet' => 'Hełm Rekruta', 
                    'boots' => 'Stalowe Trzewiki', 'amulet' => 'Amulet Ucznia', 'ring' => 'Pierścień Ucznia'
                ]
            ],
            [
                'level' => 10, 'scale' => 4.0,
                'names' => [
                    'sword' => 'Rycerski Miecz', 'armor' => 'Stalowa Zbroja', 'helmet' => 'Hełm Rycerza', 
                    'boots' => 'Trzewiki Rycerza', 'amulet' => 'Amulet Rycerza', 'ring' => 'Pierścień Rycerza'
                ]
            ],
            [
                'level' => 20, 'scale' => 10.0,
                'names' => [
                    'sword' => 'Wzmocniony Stalowy Miecz', 'armor' => 'Wzmocniona Stalowa Zbroja', 'helmet' => 'Wzmocniony Stalowy Hełm', 
                    'boots' => 'Wzmocnione Stalowe Trzewiki', 'amulet' => 'Wzmocniony Amulet', 'ring' => 'Wzmocniony Pierścień'
                ]
            ],
            [
                'level' => 30, 'scale' => 22.0,
                'names' => [
                    'sword' => 'Ostrze Szlacheckie', 'armor' => 'Zbroja Szlachcica', 'helmet' => 'Hełm Szlachcica', 
                    'boots' => 'Trzewiki Wzmocnione Zaklęciem', 'amulet' => 'Szlachecki Amulet', 'ring' => 'Szlachecki Pierścień'
                ]
            ],
            [
                'level' => 40, 'scale' => 45.0,
                'names' => [
                    'sword' => 'Ostrze Weterana', 'armor' => 'Pancerz Weterana', 'helmet' => 'Hełm Weterana', 
                    'boots' => 'Trzewiki Weterana', 'amulet' => 'Amulet Weterana', 'ring' => 'Pierścień Weterana'
                ]
            ],
            [
                'level' => 50, 'scale' => 100.0,
                'names' => [
                    'sword' => 'Mistrzowski Miecz', 'armor' => 'Mistrzowski Pancerz', 'helmet' => 'Mistrzowski Hełm', 
                    'boots' => 'Mistrzowskie Trzewiki', 'amulet' => 'Mistrzowski Amulet', 'ring' => 'Mistrzowski Pierścień'
                ]
            ],
            // Zestaw Gladiatora na poziom 55 (Arena)
            [
                'level' => 55, 'scale' => 160.0, 'merchant' => 'gladiator',
                'names' => [
                    'sword' => 'Miecz Niezwyciężonego Gladiatora', 'armor' => 'Pancerz Złotego Lwa', 'helmet' => 'Hełm Krwawej Areny', 
                    'boots' => 'Sabatony Triumfu', 'amulet' => 'Amulet Czempiona', 'ring' => 'Pierścień Chwały'
                ]
            ],
            [
                'level' => 60, 'scale' => 250.0,
                'names' => [
                    'sword' => 'Runiczny Miecz', 'armor' => 'Runiczny Pancerz', 'helmet' => 'Runiczny Hełm', 
                    'boots' => 'Runiczne Trzewiki', 'amulet' => 'Runiczny Amulet', 'ring' => 'Runiczny Pierścień'
                ]
            ],
            [
                'level' => 70, 'scale' => 600.0,
                'names' => [
                    'sword' => 'Bojowy Miecz', 'armor' => 'Bojowy Pancerz', 'helmet' => 'Bojowy Hełm', 
                    'boots' => 'Bojowe Trzewiki', 'amulet' => 'Bojowy Amulet', 'ring' => 'Bojowy Pierścień'
                ]
            ],
            [
                'level' => 80, 'scale' => 1800.0,
                'names' => [
                    'sword' => 'Obsydianowy Miecz', 'armor' => 'Obsydianowy Pancerz', 'helmet' => 'Obsydianowy Hełm', 
                    'boots' => 'Obsydianowe Trzewiki', 'amulet' => 'Obsydianowy Amulet', 'ring' => 'Obsydianowy Pierścień'
                ]
            ],
            [
                'level' => 90, 'scale' => 4500.0,
                'names' => [
                    'sword' => 'Tytanowy Miecz', 'armor' => 'Tytanowy Pancerz', 'helmet' => 'Tytanowy Hełm', 
                    'boots' => 'Tytanowe Trzewiki', 'amulet' => 'Tytanowy Amulet', 'ring' => 'Tytanowy Pierścień'
                ]
            ]
        ];

        $generatedCount = 0;

        foreach ($themes as $themeIndex => $theme) {
            $merchantTarget = $theme['merchant'] ?? null;

            foreach ($prototypes as $protoKey => $proto) {
                if (!isset($theme['names'][$protoKey])) {
                    continue;
                }

                // UWAGA (itemizacja przedziałowa): każdy stat to teraz przedział [min,max]
                // zamiast stałej liczby - patrz statRange() powyżej i ItemStatRoller.
                $scaledStats = [];
                foreach ($proto['stats'] as $statName => $baseValue) {
                    if (in_array($statName, ['double_strike_chance', 'bleed_chance', 'poison_chance', 'armor_pen_pct', 'magic_infusion_chance', 'magic_burst_chance'], true)) {
                        // Stałe, zbalansowane wartości procentowe specjalizacji broni (np. Miecz: 5-15%)
                        $variance = $statName === 'double_strike_chance' ? 0.50 : 0.33;
                        $scaledStats[$statName] = $this->statRange($baseValue, $variance);
                        continue;
                    }
                    if ($statName === 'crit_chance') {
                        $cap = 15;
                        $critVal = min($cap, $baseValue + ($themeIndex * 0.5));
                        $isWeaponAbove15 = (($proto['type'] ?? '') === 'weapon' && ($theme['level'] ?? 0) > 15);
                        $isRingOrNecklace = in_array($protoKey, ['ring', 'amulet'], true) || in_array($proto['slot'] ?? '', ['ring', 'neck'], true);

                        if ($isWeaponAbove15 || $isRingOrNecklace) {
                            $critVal = $critVal * 0.5;
                        }

                        $scaledStats[$statName] = $this->statRange($critVal, 0.25, $cap);
                        continue;
                    }
                    $scaledValue = $baseValue * $theme['scale'];
                    $scaledStats[$statName] = $this->statRange(max(1, $scaledValue));
                }

                if ($merchantTarget === 'gladiator') {
                    // Zestaw Gladiatora to ekwipunek pod Arenę PvP - dokłada stały
                    // afiks "Silny przeciwko Bohaterom" (patrz docs/modules/combat.md,
                    // sekcja 8), który poza tym da się zdobyć tylko losowo z zaklęć
                    // Czarownicy (`EnchantmentStrategy::strong_vs_hero`, 5-20%).
                    $scaledStats['strong_vs_hero'] = [10, 10];
                }

                $name = $theme['names'][$protoKey];
                $iconName = Str::slug($name);

                $template = ItemTemplate::where('name', $name)->first();
                if (!$template) {
                    $templateId = ($name === 'Miecz Nowicjusza') ? '01k4jpx94j70x2vv10b835nov1' : (string) Str::ulid();
                    $template = ItemTemplate::create([
                        'id' => $templateId,
                        'name' => $name,
                        'type' => $proto['type'],
                        'sub_type' => $proto['sub_type'] ?? null,
                        'slot' => $proto['slot'],
                        'level_requirement' => $theme['level'],
                        'base_stats' => $scaledStats,
                        'description' => $merchantTarget === 'gladiator' ? "Artefakt z krwawej areny." : "Standardowe wyposażenie poziomu " . $theme['level'] . ".",
                        'icon' => $iconName,
                        'rarity_weights' => [
                            'common' => 100
                        ],
                    ]);
                } else {
                    // Synchronizuj też statystyki przy ponownym odpaleniu seedera.
                    $template->update([
                        'sub_type' => $proto['sub_type'] ?? null,
                        'base_stats' => $scaledStats,
                    ]);
                }

                // Przypisanie do handlarza (Handlarz sprzedaje zarówno bronie, jak i zbroje/biżuterię
                // w jednym, wspólnym asortymencie - patrz docs/modules/merchant.md)
                $merchantId = $merchantTarget === 'gladiator' ? 'gladiator' : 'merchant';

                MerchantItem::updateOrCreate(
                    [
                        'merchant_id' => $merchantId,
                        'item_template_id' => $template->id,
                    ],
                    [
                        'required_level' => $theme['level'],
                        'currency_type' => $merchantTarget === 'gladiator' ? 'arena_tokens' : 'gold',
                        'price' => $merchantTarget === 'gladiator' ? 400 : 0,
                    ]
                );


                $generatedCount++;
            }
        }

        $this->command->info('Created ' . $generatedCount . ' shop equipments and assigned them to merchants.');
    }
}
