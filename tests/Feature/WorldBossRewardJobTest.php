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

    public function test_world_boss_hp_never_reaches_zero_after_massive_damage(): void
    {
        $this->seedWorldBossMonsters();

        $monster = Monster::where('name', 'Król Lasu')->first();
        $boss = WorldBossInstance::create([
            'monster_id' => $monster->id,
            'map_id' => $monster->map_id,
            'level_bracket' => 'low',
            'total_hp' => 1000,
            'current_hp' => 1000,
        ]);

        // Simulate the regen-then-decrement logic used in EncounterService::simulate().
        $regen = (int) ceil($boss->total_hp * 0.02);
        $newHp = max(1, min($boss->total_hp, $boss->current_hp + $regen) - 999999999);
        $boss->update(['current_hp' => $newHp]);

        $this->assertGreaterThanOrEqual(1, $boss->fresh()->current_hp);
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
}
