<?php

namespace Tests\Feature;

use App\Application\Combat\EncounterService;
use App\Application\Combat\WorldBossService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Mail;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\WorldBossInstance;
use App\Jobs\WorldBossRewardJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorldBossRewardJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeCharacter(string $name): Character
    {
        $user = User::factory()->create();

        return Character::create([
            'user_id' => $user->id,
            'name' => $name,
            'level' => 20,
            'gold' => 0,
        ]);
    }

    private function seedKeyTemplates(): void
    {
        foreach (['Klucz Katakumb', 'Klucz Krypty', 'Klucz Pustkowi', 'Klucz Cytadeli', 'Klucz Otchłani'] as $name) {
            ItemTemplate::create([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'type' => 'material',
                'sub_type' => 'key',
                'level_requirement' => 1,
            ]);
        }
    }

    private function seedWorldBossMonsters(): void
    {
        $map = Map::create(['name' => 'Test Map', 'level_min' => 0, 'level_max' => 99]);

        foreach (WorldBossService::BRACKET_POOLS as $names) {
            foreach ($names as $name) {
                Monster::create([
                    'map_id' => $map->id,
                    'name' => $name,
                    'type' => 'animal',
                    'level' => 10,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 100000, 'atk' => 10, 'def' => 5, 'agi' => 2],
                ]);
            }
        }
    }

    public function test_ensure_bosses_spawned_creates_exactly_one_instance_per_bracket(): void
    {
        $this->seedWorldBossMonsters();

        app(WorldBossService::class)->ensureBossesSpawned();

        $this->assertEquals(3, WorldBossInstance::count());
        $this->assertEquals(
            ['high', 'low', 'mid'],
            WorldBossInstance::pluck('level_bracket')->sort()->values()->toArray()
        );
    }

    public function test_reward_job_grants_correct_tiers_and_key_template(): void
    {
        $this->seedKeyTemplates();
        $this->seedWorldBossMonsters();

        $monster = Monster::where('name', 'Wódz Orków')->first();
        $boss = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $monster->map_id,
            'level_bracket' => 'mid',
            'total_hp' => 100000,
            'current_hp' => 90000,
        ]);

        $characters = [];
        for ($i = 1; $i <= 10; $i++) {
            $characters[$i] = $this->makeCharacter("Fighter{$i}");
            WorldBossDamageLog::create([
                'world_boss_instance_id' => $boss->id,
                'character_id' => $characters[$i]->id,
                // Higher place number -> less damage, so ranking matches place order.
                'damage' => 100000 - ($i * 1000),
            ]);
        }

        (new WorldBossRewardJob())->handle();

        // Instance for this bracket must have been cleared and respawned.
        $this->assertDatabaseMissing('world_boss_instances', ['id' => $boss->id]);
        $this->assertDatabaseMissing('world_boss_damage_logs', ['world_boss_instance_id' => $boss->id]);
        $this->assertEquals(1, WorldBossInstance::where('level_bracket', 'mid')->count());

        $keyTemplate = ItemTemplate::where('name', 'Klucz Pustkowi')->first();

        $expectedTiers = [
            1 => ['gems' => 50, 'keys' => 5],
            2 => ['gems' => 30, 'keys' => 5],
            3 => ['gems' => 30, 'keys' => 5],
            4 => ['gems' => 0, 'keys' => 3],
            5 => ['gems' => 0, 'keys' => 3],
            6 => ['gems' => 0, 'keys' => 3],
            7 => ['gems' => 0, 'keys' => 1],
            8 => ['gems' => 0, 'keys' => 1],
            9 => ['gems' => 0, 'keys' => 1],
        ];

        foreach ($expectedTiers as $place => $expected) {
            $character = $characters[$place];
            $mail = Mail::where('to_character_id', $character->id)->first();
            $this->assertNotNull($mail, "Brak maila dla miejsca {$place}");

            $gemsAttachment = collect($mail->attachments)->firstWhere('type', 'gems');
            $itemAttachment = collect($mail->attachments)->firstWhere('type', 'item');

            if ($expected['gems'] > 0) {
                $this->assertNotNull($gemsAttachment, "Brak gemów w mailu dla miejsca {$place}");
                $this->assertEquals($expected['gems'], $gemsAttachment['qty']);
            } else {
                $this->assertNull($gemsAttachment, "Nieoczekiwane gemy w mailu dla miejsca {$place}");
            }

            $this->assertNotNull($itemAttachment, "Brak klucza w mailu dla miejsca {$place}");
            $itemInstance = \App\Infrastructure\Persistence\ItemInstance::find($itemAttachment['id']);
            $this->assertEquals($keyTemplate->id, $itemInstance->template_id);
            $this->assertEquals($expected['keys'], $itemInstance->stack_size);
        }

        // 10th place gets nothing.
        $this->assertNull(Mail::where('to_character_id', $characters[10]->id)->first());
    }

    public function test_world_boss_shared_pool_never_regenerates(): void
    {
        // UWAGA (fix 2026-07-30, doprecyzowane przez użytkownika): world boss NIE regeneruje
        // współdzielonej puli current_hp - "regeneracja co turę" z pierwotnej prośby dotyczyła
        // wyłącznie ochrony pojedynczej walki gracza przed przypadkowym wyzerowaniem (co i tak
        // jest niemożliwe, bo damageDealt liczone jest względem osobnej, fikcyjnej puli
        // 999 999 999 - patrz EncounterService::simulate()). current_hp musi być czystą,
        // monotonicznie malejącą sumą zadanych obrażeń.
        $this->seedWorldBossMonsters();

        $monster = Monster::where('name', 'Król Lasu')->first();
        $boss = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $monster->map_id,
            'level_bracket' => 'low',
            'total_hp' => 999999999,
            'current_hp' => 999999999,
        ]);

        $character = $this->makeCharacter('RegenChecker');
        $character->update(['level' => 20, 'attributes' => ['str' => 200, 'int' => 20, 'vit' => 200, 'agi' => 50]]);

        $service = app(EncounterService::class);
        $startResult = $service->start($character, $monster->map, $monster);
        $this->assertTrue($startResult->isOk());

        $simResult = $service->simulate($startResult->getPayload());
        $this->assertTrue($simResult->isOk());

        $damageDealt = $simResult->getPayload()['rewards']['damage_dealt'];
        $this->assertGreaterThan(0, $damageDealt);

        // current_hp must have dropped by EXACTLY damageDealt - no regen added back.
        $this->assertEquals(999999999 - $damageDealt, $boss->fresh()->current_hp);
    }

    public function test_world_boss_can_be_fully_depleted_and_locks_further_attacks(): void
    {
        $this->seedWorldBossMonsters();

        $monster = Monster::where('name', 'Król Lasu')->first();
        $boss = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $monster->map_id,
            'level_bracket' => 'low',
            'total_hp' => 1000,
            'current_hp' => 0, // A sufficiently strong hit (or combined community damage) can zero it out.
        ]);

        $character = $this->makeCharacter('LateAttacker');
        $character->update(['level' => 20]);

        $service = app(EncounterService::class);
        $result = $service->start($character, $monster->map, $monster);

        $this->assertTrue($result->isError());
        $this->assertEquals('WORLD_BOSS_DEFEATED', $result->getErrorCode());
        $this->assertDatabaseMissing('world_boss_damage_logs', [
            'world_boss_instance_id' => $boss->id,
            'character_id' => $character->id,
        ]);
    }

    public function test_character_outside_bracket_cannot_attack_world_boss(): void
    {
        $this->seedWorldBossMonsters();
        app(WorldBossService::class)->ensureBossesSpawned();

        $lowBoss = WorldBossInstance::with('monster.map')->where('level_bracket', 'low')->first();
        $this->assertNotNull($lowBoss);

        // A level 95 character must not be able to attack a 'low' bracket (0-35) world boss,
        // even though Map::isAccessibleBy() would allow it (it only enforces the lower bound -
        // see EncounterService::start() comment).
        $highLevelCharacter = $this->makeCharacter('HighLevelHero');
        $highLevelCharacter->update(['level' => 95]);

        $service = app(EncounterService::class);
        $result = $service->start($highLevelCharacter, $lowBoss->monster->map, $lowBoss->monster);

        $this->assertTrue($result->isError());
        $this->assertEquals('WRONG_LEVEL_BRACKET', $result->getErrorCode());
        $this->assertDatabaseMissing('world_boss_damage_logs', [
            'world_boss_instance_id' => $lowBoss->id,
            'character_id' => $highLevelCharacter->id,
        ]);

        // A level within the bracket must still be allowed through (sanity check the fix
        // isn't overly strict).
        $inBracketCharacter = $this->makeCharacter('InBracketHero');
        $inBracketCharacter->update(['level' => 20]);

        $okResult = $service->start($inBracketCharacter, $lowBoss->monster->map, $lowBoss->monster);
        $this->assertTrue($okResult->isOk());
    }

    public function test_orphaned_null_bracket_instance_does_not_shadow_active_bracketed_one(): void
    {
        $this->seedWorldBossMonsters();

        $monster = Monster::where('name', 'Król Trolli')->first();

        // Simulate a leftover row from before the bracket rework (migration does not backfill
        // level_bracket on pre-existing rows) sharing the same map_id + monster_id as a fresh,
        // properly-bracketed instance created afterwards.
        $orphan = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $monster->map_id,
            'level_bracket' => null,
            'total_hp' => 140000,
            'current_hp' => 140000,
        ]);

        $activeInstance = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $monster->map_id,
            'level_bracket' => 'low',
            'total_hp' => 140000,
            'current_hp' => 140000,
        ]);

        $character = $this->makeCharacter('BracketVictim');
        $character->update(['level' => 33]);

        $service = app(EncounterService::class);
        $startResult = $service->start($character, $monster->map, $monster);

        $this->assertTrue($startResult->isOk(), 'A level-33 character must be allowed to attack a low-bracket boss even if an orphaned null-bracket row exists for the same monster/map.');

        $simResult = $service->simulate($startResult->getPayload());
        $this->assertTrue($simResult->isOk());

        $this->assertDatabaseHas('world_boss_damage_logs', [
            'world_boss_instance_id' => $activeInstance->id,
            'character_id' => $character->id,
        ]);
        $this->assertDatabaseMissing('world_boss_damage_logs', [
            'world_boss_instance_id' => $orphan->id,
        ]);
    }

    public function test_character_within_world_boss_bracket_can_attack_even_if_map_min_level_is_higher(): void
    {
        // High bracket is 65-99. Map 'Wieża Magów' has level_min 75, level_max 85.
        // A character of level 66 is in 'high' bracket (65-99) and should be allowed to attack Arcymag.
        $map = Map::create(['name' => 'Wieża Magów', 'level_min' => 75, 'level_max' => 85]);
        $monster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Arcymag',
            'type' => 'animal',
            'level' => 85,
            'rank' => 'worldboss',
            'stats' => ['hp' => 100000, 'atk' => 10, 'def' => 5, 'agi' => 2],
        ]);

        $boss = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $map->id,
            'level_bracket' => 'high',
            'total_hp' => 1000000,
            'current_hp' => 1000000,
        ]);

        $char66 = $this->makeCharacter('HeroLevel66');
        $char66->update(['level' => 66]);

        $service = app(EncounterService::class);
        $result = $service->start($char66, $map, $monster);

        $this->assertTrue($result->isOk(), 'Level 66 character must be allowed to fight Arcymag (bracket high: 65-99) even on a level 75-85 map.');
    }

    public function test_claim_mail_action_preserves_item_stack_size(): void
    {
        $this->seedKeyTemplates();
        $char = $this->makeCharacter('Claimer');
        $template = ItemTemplate::where('name', 'Klucz Otchłani')->first();

        $itemInstance = \App\Infrastructure\Persistence\ItemInstance::create([
            'template_id' => $template->id,
            'owner_character_id' => $char->id,
            'stack_size' => 5,
            'rarity' => 'uncommon',
            'location' => 'mail',
        ]);

        $mail = Mail::create([
            'to_character_id' => $char->id,
            'subject' => 'Nagroda',
            'body' => 'Otrzymujesz: 5 klucze (Klucz Otchłani).',
            'attachments' => [
                ['type' => 'item', 'id' => $itemInstance->id],
            ],
            'claimed' => false,
        ]);

        $action = new \App\Application\Mail\Actions\ClaimMailAction();
        $result = $action->execute($char, $mail);

        $this->assertTrue($result->isOk());
        $this->assertTrue($mail->fresh()->claimed);

        $this->assertDatabaseHas('item_ledgers', [
            'character_id' => $char->id,
            'action' => 'mail_claim',
            'quantity_change' => 5,
        ]);
    }
}
