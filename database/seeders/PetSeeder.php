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
        // 1. Gatunki Chowańców (PetTemplate) - pula nazw+ikon per tier, z której
        // IncubatorService::hatchEgg() losuje przy wykluciu (patrz pickSpecies()).
        // Zarządzane też z panelu admina "Zarządzanie Zwierzakami" (Filament/Livewire) -
        // admin może dodawać kolejne gatunki do dowolnego tieru bez zmian w kodzie.
        // Ikony to nazwy ikon FontAwesome (bez emoji, zgodnie z docs/agent_rules.md pkt 8).
        $pets = [
            ['name' => 'Leśny Wilk', 'tier' => 1, 'rarity' => 'common', 'icon' => 'dog', 'base_stats' => ['str' => 2, 'agi' => 3, 'int' => 0, 'vit' => 1]],
            ['name' => 'Skalny Golem', 'tier' => 2, 'rarity' => 'uncommon', 'icon' => 'chess-rook', 'base_stats' => ['str' => 4, 'agi' => 0, 'int' => 0, 'vit' => 5]],
            ['name' => 'Magiczna Wróżka', 'tier' => 3, 'rarity' => 'rare', 'icon' => 'wand-magic-sparkles', 'base_stats' => ['str' => 0, 'agi' => 2, 'int' => 6, 'vit' => 2]],
            ['name' => 'Mroczny Smok', 'tier' => 4, 'rarity' => 'epic', 'icon' => 'dragon', 'base_stats' => ['str' => 8, 'agi' => 5, 'int' => 8, 'vit' => 6]],
            ['name' => 'Ognisty Feniks', 'tier' => 5, 'rarity' => 'epic', 'icon' => 'fire', 'base_stats' => ['str' => 6, 'agi' => 6, 'int' => 8, 'vit' => 4]],
            ['name' => 'Świetlisty Duch', 'tier' => 6, 'rarity' => 'legendary', 'icon' => 'ghost', 'base_stats' => ['str' => 4, 'agi' => 8, 'int' => 10, 'vit' => 6]],
        ];

        foreach ($pets as $pet) {
            PetTemplate::updateOrCreate(['name' => $pet['name']], $pet);
        }

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
