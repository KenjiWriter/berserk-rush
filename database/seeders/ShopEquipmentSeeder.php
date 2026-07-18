<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MerchantItem;
use Illuminate\Support\Str;

class ShopEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        // Usunięcie starych przedmiotów kupców
        MerchantItem::query()->whereIn('merchant_id', ['armorsmith', 'weaponsmith', 'gladiator'])->delete();

        $prototypes = [
            'sword'   => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['attack_min' => 2, 'attack_max' => 5, 'str_bonus' => 1, 'agi_bonus' => 1, 'int_bonus' => 1]],
            'armor'   => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 4, 'hp_bonus' => 15, 'str_bonus' => 1, 'agi_bonus' => 1, 'int_bonus' => 1]],
            'helmet'  => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 2, 'hp_bonus' => 8, 'str_bonus' => 1, 'agi_bonus' => 1, 'int_bonus' => 1]],
            'boots'   => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 1, 'hp_bonus' => 5, 'str_bonus' => 1, 'agi_bonus' => 1, 'int_bonus' => 1]],
            'amulet'  => ['type' => 'accessory', 'slot' => 'neck', 'stats' => ['hp_bonus' => 15, 'mana_bonus' => 10, 'vit_bonus' => 1]],
            'ring'    => ['type' => 'accessory', 'slot' => 'ring', 'stats' => ['str_bonus' => 1, 'agi_bonus' => 1, 'int_bonus' => 1]],
        ];

        // Skala jest niższa niż w craftingu (~80%)
        $themes = [
            [
                'level' => 1, 'scale' => 0.8,
                'names' => [
                    'sword' => 'Miecz Nowicjusza', 'armor' => 'Zbroja Rekruta', 'helmet' => 'Hełm Rekruta', 
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

        foreach ($themes as $theme) {
            $merchantTarget = $theme['merchant'] ?? null;

            foreach ($prototypes as $protoKey => $proto) {
                
                $scaledStats = [];
                foreach ($proto['stats'] as $statName => $baseValue) {
                    $scaledValue = $baseValue * $theme['scale'];
                    // Zaokrąglij w górę i upewnij się, że minimum to 1
                    $scaledStats[$statName] = max(1, (int) ceil($scaledValue));
                }

                $name = $theme['names'][$protoKey];
                $iconName = Str::slug($name);

                // Sprawdzamy czy template istnieje z seedera ItemTemplate (Miecz nowicjusza był wpisany ręcznie z innym ID, usuniemy go wcześniej lub nadpiszemy/użyjemy firstOrCreate wg nazwy).
                // Najlepiej usunąć dotychczasowego miecza nowicjusza żeby nie było duplikatów.
                ItemTemplate::where('name', $name)->delete();

                $template = ItemTemplate::create([
                    'id' => Str::ulid(),
                    'name' => $name,
                    'type' => $proto['type'],
                    'slot' => $proto['slot'],
                    'level_requirement' => $theme['level'],
                    'base_stats' => $scaledStats,
                    'description' => $merchantTarget === 'gladiator' ? "Artefakt z krwawej areny." : "Standardowe wyposażenie poziomu " . $theme['level'] . ".",
                    'icon' => $iconName,
                    'rarity_weights' => [
                        'common' => 100 // Sklepowe są zawsze pospolite
                    ],
                ]);

                // Przypisanie do handlarza
                if ($merchantTarget === 'gladiator') {
                    $merchantId = 'gladiator';
                } else {
                    $merchantId = ($proto['type'] === 'weapon') ? 'weaponsmith' : 'armorsmith';
                }

                MerchantItem::create([
                    'merchant_id' => $merchantId,
                    'item_template_id' => $template->id,
                    'required_level' => $theme['level'],
                ]);

                $generatedCount++;
            }
        }

        $this->command->info('Created ' . $generatedCount . ' shop equipments and assigned them to merchants.');
    }
}
