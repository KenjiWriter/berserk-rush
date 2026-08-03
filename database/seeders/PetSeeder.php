<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\PetTemplate;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MerchantItem;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;

class PetSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Gatunki Chowańców (PetTemplate) - pula nazw+ikon+archetypów per tier,
        // z której IncubatorService/PetFusionService losują przez PetSpeciesPicker.
        // Zarządzane też z panelu admina "Zarządzanie Zwierzakami" - admin może
        // dodawać kolejne gatunki do dowolnego tieru/archetypu bez zmian w kodzie.
        // 3 archetypy x 6 tierów = 18 gatunków (patrz docs/modules/pets.md sekcja
        // "Pasywka Rodzaju"):
        // - attacker: więcej % obrażeń (DPS)
        // - defense: więcej % obrony i punktów życia
        // - support: więcej % szansy na unik i niższe koszty many
        // Ikony to nazwy plików w `public/assets/items/` (bez rozszerzenia) - na razie
        // placeholder z 8 ogólnych plików dostarczonych przez zespół (pet_wolf/
        // pet_golem/pet_dragon/pet_phoenix/pet_eagle/pet_bear/pet_tiger/pet_spirit),
        // część gatunków dzieli tę samą ikonę do czasu dostarczenia dedykowanej grafiki.
        $rarityByTier = [1 => 'common', 2 => 'uncommon', 3 => 'rare', 4 => 'epic', 5 => 'epic', 6 => 'legendary'];

        $pets = [
            // --- Atakujący (więcej % DPS) ---
            ['name' => 'Dziki Kot', 'tier' => 1, 'archetype' => 'attacker', 'icon' => 'pet_tiger', 'base_stats' => ['str' => 3, 'agi' => 3, 'int' => 0, 'vit' => 1]],
            ['name' => 'Goblin Rozpruwacz', 'tier' => 2, 'archetype' => 'attacker', 'icon' => 'pet_bear', 'base_stats' => ['str' => 5, 'agi' => 4, 'int' => 0, 'vit' => 2]],
            ['name' => 'Cienisty Wilk', 'tier' => 3, 'archetype' => 'attacker', 'icon' => 'pet_wolf', 'base_stats' => ['str' => 7, 'agi' => 6, 'int' => 1, 'vit' => 3]],
            ['name' => 'Ognisty Gryf', 'tier' => 4, 'archetype' => 'attacker', 'icon' => 'pet_phoenix', 'base_stats' => ['str' => 10, 'agi' => 8, 'int' => 2, 'vit' => 4]],
            ['name' => 'Demoniczna Mantykora', 'tier' => 5, 'archetype' => 'attacker', 'icon' => 'pet_eagle', 'base_stats' => ['str' => 13, 'agi' => 10, 'int' => 3, 'vit' => 5]],
            ['name' => 'Smok Pustki', 'tier' => 6, 'archetype' => 'attacker', 'icon' => 'pet_dragon', 'base_stats' => ['str' => 16, 'agi' => 12, 'int' => 4, 'vit' => 6]],

            // --- Obrony (więcej % obrony/życia) ---
            ['name' => 'Żółw', 'tier' => 1, 'archetype' => 'defense', 'icon' => 'pet_golem', 'base_stats' => ['str' => 1, 'agi' => 1, 'int' => 0, 'vit' => 4]],
            ['name' => 'Kamienny Chrząszcz', 'tier' => 2, 'archetype' => 'defense', 'icon' => 'pet_golem', 'base_stats' => ['str' => 2, 'agi' => 1, 'int' => 0, 'vit' => 6]],
            ['name' => 'Kościany Strażnik', 'tier' => 3, 'archetype' => 'defense', 'icon' => 'pet_spirit', 'base_stats' => ['str' => 3, 'agi' => 1, 'int' => 1, 'vit' => 9]],
            ['name' => 'Runiczny Golem', 'tier' => 4, 'archetype' => 'defense', 'icon' => 'pet_golem', 'base_stats' => ['str' => 4, 'agi' => 2, 'int' => 1, 'vit' => 12]],
            ['name' => 'Nieumarły Behemot', 'tier' => 5, 'archetype' => 'defense', 'icon' => 'pet_bear', 'base_stats' => ['str' => 5, 'agi' => 2, 'int' => 2, 'vit' => 15]],
            ['name' => 'Tytan Wieczności', 'tier' => 6, 'archetype' => 'defense', 'icon' => 'pet_spirit', 'base_stats' => ['str' => 6, 'agi' => 3, 'int' => 2, 'vit' => 18]],

            // --- Wspomagający (więcej % uniku / niższe koszty many) ---
            ['name' => 'Świetlik', 'tier' => 1, 'archetype' => 'support', 'icon' => 'pet_spirit', 'base_stats' => ['str' => 0, 'agi' => 2, 'int' => 3, 'vit' => 1]],
            ['name' => 'Leśny Duszek', 'tier' => 2, 'archetype' => 'support', 'icon' => 'pet_eagle', 'base_stats' => ['str' => 0, 'agi' => 3, 'int' => 5, 'vit' => 2]],
            ['name' => 'Wróżka Księżycowa', 'tier' => 3, 'archetype' => 'support', 'icon' => 'pet_spirit', 'base_stats' => ['str' => 1, 'agi' => 4, 'int' => 7, 'vit' => 2]],
            ['name' => 'Pegaz', 'tier' => 4, 'archetype' => 'support', 'icon' => 'pet_eagle', 'base_stats' => ['str' => 1, 'agi' => 5, 'int' => 9, 'vit' => 3]],
            ['name' => 'Feniks Odrodzenia', 'tier' => 5, 'archetype' => 'support', 'icon' => 'pet_phoenix', 'base_stats' => ['str' => 2, 'agi' => 6, 'int' => 11, 'vit' => 4]],
            ['name' => 'Serafin Przeznaczenia', 'tier' => 6, 'archetype' => 'support', 'icon' => 'pet_phoenix', 'base_stats' => ['str' => 2, 'agi' => 7, 'int' => 13, 'vit' => 5]],
        ];

        foreach ($pets as $pet) {
            $pet['rarity'] = $rarityByTier[$pet['tier']];
            PetTemplate::updateOrCreate(['name' => $pet['name']], $pet);
        }

        // Usuń poprzednią (przed archetypami) generyczną pulę 6 gatunków - zastąpione
        // przez powyższe 18 (3 archetypy x 6 tierów). Pety już wyklute/zfuzjonowane pod
        // starymi nazwami NIE są ruszane (Pet.name/icon to kopia, nie referencja do
        // szablonu) - zostają bez archetypu/pasywki, zamiast być retroaktywnie zmieniane
        // po raz kolejny.
        PetTemplate::whereIn('name', [
            'Leśny Wilk', 'Skalny Golem', 'Magiczna Wróżka', 'Mroczny Smok', 'Ognisty Feniks', 'Świetlisty Duch',
        ])->whereNull('archetype')->delete();

        // 2. Jajka chowańców - 1 na każdy z 6 tierów (Pospolity..Legendarny).
        // `egg_tier` jest jedynym źródłem prawdy dla wykluwania (patrz
        // ItemInstance::getEggTier()) - dawne zgadywanie po nazwie/id zostało usunięte.
        $eggs = [
            ['id' => 'egg-t1', 'name' => 'Pospolite Jajko Chowańca', 'egg_tier' => 1, 'level_requirement' => 1, 'icon' => 'pospolite-jajo-chowanca.png', 'description' => 'Wykluje się z niego Pospolity chowaniec.'],
            ['id' => 'egg-t2', 'name' => 'Zwykłe Jajko Chowańca', 'egg_tier' => 2, 'level_requirement' => 10, 'icon' => 'zwykle-jajo-chowanca.png', 'description' => 'Zwiększona szansa na Zwykłego chowańca.'],
            ['id' => 'egg-t3', 'name' => 'Nietypowe Jajko Chowańca', 'egg_tier' => 3, 'level_requirement' => 20, 'icon' => 'nietypowe-jajo-chowanca.png', 'description' => 'Zwiększona szansa na Nietypowego chowańca.'],
            ['id' => 'egg-t4', 'name' => 'Rzadkie Jajko Chowańca', 'egg_tier' => 4, 'level_requirement' => 30, 'icon' => 'rzadkie-jajo-chowanca.png', 'description' => 'Zwiększona szansa na Rzadkiego chowańca.'],
            ['id' => 'egg-t5', 'name' => 'Epickie Jajko Chowańca', 'egg_tier' => 5, 'level_requirement' => 45, 'icon' => 'epickie-jajo-chowanca.png', 'description' => 'Zwiększona szansa na Epickiego chowańca.'],
            ['id' => 'egg-t6', 'name' => 'Legendarne Jajko Chowańca', 'egg_tier' => 6, 'level_requirement' => 60, 'icon' => 'legendarne-jajo-chowanca.png', 'description' => 'Potężne jajo skrywające Legendarnego chowańca.'],
        ];

        foreach ($eggs as $egg) {
            $egg['type'] = 'egg';
            $existing = ItemTemplate::find($egg['id']);
            if ($existing) {
                $existing->update($egg);
            } else {
                ItemTemplate::create($egg);
            }
        }

        // 3. Ekwipunek Peta (obroża + charm) - sprzedawane u Handlarza jak zwykły
        // ekwipunek (patrz docs/modules/merchant.md), zakładane w PetsComponent.
        $petGear = [
            [
                'id' => 'pet-collar-basic',
                'name' => 'Skórzana Obroża',
                'type' => 'pet_collar',
                'level_requirement' => 10,
                'base_stats' => ['str_bonus' => 2, 'agi_bonus' => 2, 'int_bonus' => 2, 'vit_bonus' => 2],
                'icon' => 'skorzana-obroza.png',
                'description' => 'Prosta obroża dodająca niewielki bonus do statystyk chowańca.',
                'price' => 500,
                'required_level' => 10,
            ],
            [
                'id' => 'pet-collar-silver',
                'name' => 'Posrebrzana Obroża',
                'type' => 'pet_collar',
                'level_requirement' => 40,
                'base_stats' => ['str_bonus' => 5, 'agi_bonus' => 5, 'int_bonus' => 5, 'vit_bonus' => 5],
                'icon' => 'posrebrzana-obroza.png',
                'description' => 'Zdobiona obroża znacząco wzmacniająca statystyki chowańca.',
                'price' => 2500,
                'required_level' => 40,
            ],
            [
                'id' => 'pet-charm-amulet',
                'name' => 'Amulet Feralnej Mocy',
                'type' => 'pet_charm',
                'level_requirement' => 30,
                'base_stats' => ['str_bonus' => 8, 'agi_bonus' => 8, 'int_bonus' => 8, 'vit_bonus' => 8],
                'icon' => 'amulet-feralnej-mocy.png',
                'description' => 'Artefakt mocy zakładany w slocie charmu chowańca.',
                'price' => 3000,
                'required_level' => 30,
            ],
            [
                'id' => 'pet-charm-bag',
                'name' => 'Sakwa Chowańców',
                'type' => 'pet_charm',
                'level_requirement' => 30,
                'base_stats' => ['pet_stable_bonus' => 10],
                'icon' => 'sakwa-chowancow.png',
                'description' => 'Powiększa Twoją stajnię o dodatkowe miejsca na chowańce, zamiast wzmacniać statystyki.',
                'price' => 3000,
                'required_level' => 30,
            ],
        ];

        foreach ($petGear as $gear) {
            $requiredLevel = $gear['required_level'];
            $price = $gear['price'];
            unset($gear['required_level'], $gear['price']);

            $existing = ItemTemplate::find($gear['id']);
            if ($existing) {
                $existing->update($gear);
                $template = $existing;
            } else {
                $template = ItemTemplate::create($gear);
            }

            MerchantItem::updateOrCreate(
                ['merchant_id' => 'merchant', 'item_template_id' => $template->id],
                ['required_level' => $requiredLevel, 'currency_type' => 'gold', 'price' => $price]
            );
        }

        // 4. Boss Loot Table
        $bossLootTable = LootTable::firstOrCreate(
            ['name' => 'boss_dungeon_loot'],
            ['description' => 'Loot z Władcy Lochów, zawiera jajka.']
        );

        // Balans ekonomii (2026-07-29): drop przedmiotów (w tym jajek chowańców) zmniejszony
        // globalnie o 100% (waga x0 - nigdy nie wypadnie, patrz WeightedPicker::pick).
        $drops = [
            ['reward_type' => 'gold', 'ref_ulid' => null, 'min_qty' => 100, 'max_qty' => 500, 'weight' => 100],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-t1', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 0],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-t2', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 0],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-t3', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 0],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-t4', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 0],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-t5', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 0],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-t6', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 0],
        ];

        foreach ($drops as $drop) {
            // updateOrCreate (nie firstOrCreate) - żeby ponowne odpalenie seedera po zmianie
            // wag faktycznie nadpisywało istniejące wpisy, a nie tylko tworzyło brakujące.
            LootTableEntry::updateOrCreate([
                'loot_table_id' => $bossLootTable->id,
                'reward_type' => $drop['reward_type'],
                'ref_ulid' => $drop['ref_ulid'],
            ], [
                'min_qty' => $drop['min_qty'],
                'max_qty' => $drop['max_qty'],
                'weight' => $drop['weight'],
            ]);
        }

        // Usuń nieaktualne wpisy po dawnych jajkach (egg-common/egg-rare/egg-epic) z
        // tabeli dropów, żeby loot table nie trzymał martwych referencji po reworku
        // tierów. Same szablony (`item_templates`) NIE są usuwane - mogłoby to
        // kaskadowo skasować realne `item_instances` graczy, którzy już takie jajko
        // posiadają (`cascadeOnDelete` na `template_id`), zostają jako martwe/nieaktywne dane.
        LootTableEntry::where('loot_table_id', $bossLootTable->id)
            ->whereIn('ref_ulid', ['egg-common', 'egg-rare', 'egg-epic'])
            ->delete();

        // 5. Update Dungeon to have a specific Boss
        $dungeon = Dungeon::first();
        if ($dungeon) {
            $bossMonster = Monster::firstOrCreate(
                ['name' => 'Władca Lochów'],
                [
                    'map_id' => Monster::first()->map_id ?? 1,
                    'type' => 'undead',
                    'level' => 15,
                    'rank' => 'boss',
                    'stats' => ['hp' => 500, 'atk' => 30, 'def' => 15, 'crit' => 5],
                    'loot_table_id' => $bossLootTable->id
                ]
            );

            // Replace the last stage of the dungeon with this boss
            $lastStage = DungeonStage::where('dungeon_id', $dungeon->id)->orderByDesc('stage_order')->first();
            if ($lastStage) {
                $lastStage->update(['monster_id' => $bossMonster->id]);
            }
        }

        $this->command->info('Pet tiers, eggs, gear and dungeon boss drops seeded.');
    }
}
