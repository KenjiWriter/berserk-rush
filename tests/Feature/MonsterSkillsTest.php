<?php

use App\Application\Combat\EncounterService;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;

// Tests\TestCase + RefreshDatabase są nakładane globalnie na Feature/ przez tests/Pest.php.

/**
 * Buduje długą, bezpieczną walkę 1v1: bardzo wytrzymały potwór o niskim ataku vs
 * tankowa postać o niskich obrażeniach - żeby skille potwora zdążyły odpalić i dało
 * się zaobserwować ich efekt, zanim ktokolwiek zginie. Potwór dostaje przekazane
 * `abilities` (skille Fazy 2).
 */
function makeSkillFight(array $abilities): array
{
    $user = User::factory()->create(['game_stage' => 25]);
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'SkillDummy' . uniqid(),
        'level' => 10,
        'attributes' => ['str' => 1, 'int' => 1, 'vit' => 60, 'agi' => 1],
    ]);

    $map = Map::create([
        'name' => 'Skill Test Map',
        'level_min' => 1,
        'level_max' => 90,
    ]);

    $monster = Monster::create([
        'map_id' => $map->id,
        'name' => 'Caster Dummy',
        'level' => 10,
        'rank' => 'regular',
        // Wysokie HP + obrona (długa walka), niski atk (postać nie ginie za szybko).
        'stats' => ['hp' => 100000, 'atk' => 4, 'def' => 250, 'agi' => 1, 'int' => 1, 'crit' => 0.0, 'dodge' => 0.0],
        'abilities' => $abilities,
    ]);

    $service = app(EncounterService::class);
    $start = $service->start($character, $map, $monster);
    expect($start->isOk())->toBeTrue();

    $encounter = $start->getPayload();
    $sim = $service->simulate($encounter);
    expect($sim->isOk())->toBeTrue();

    return $encounter->fresh()->turns;
}

test('monster poison skill applies a DoT that ticks on the player', function () {
    $turns = makeSkillFight([
        'skills' => [
            ['name' => 'Jad Żmii', 'effect_type' => 'poison', 'value' => 0.04, 'duration' => 3, 'cooldown' => 2, 'chance' => 100],
        ],
    ]);

    // Potwór faktycznie rzucił truciznę (osobna tura-cast, effectType=poison).
    $cast = collect($turns)->firstWhere('effectType', 'poison');
    expect($cast)->not->toBeNull();
    expect($cast['actor'])->toBe('enemy');

    // DoT zatykał na graczu w którejś kolejnej turze potwora.
    $tick = collect($turns)->first(fn ($t) => ($t['playerDotDamage'] ?? 0) > 0 && ($t['playerDotType'] ?? null) === 'poison');
    expect($tick)->not->toBeNull();
});

test('monster magic direct_dmg skill deals magic damage tagged for the caster archetype', function () {
    $turns = makeSkillFight([
        'is_caster' => true,
        'skills' => [
            ['name' => 'Kula Ognia', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.8, 'duration' => 0, 'cooldown' => 2, 'chance' => 100],
        ],
    ]);

    $magicHit = collect($turns)->first(fn ($t) => !empty($t['isMagic']) && ($t['magicDamage'] ?? 0) > 0 && $t['actor'] === 'enemy');
    expect($magicHit)->not->toBeNull();
    expect($magicHit['skillName'])->toBe('Kula Ognia');
});

test('monster stun skill makes the player lose a turn (crowd_controlled)', function () {
    $turns = makeSkillFight([
        'skills' => [
            ['name' => 'Ogłuszenie', 'effect_type' => 'stun', 'value' => 1.0, 'duration' => 1, 'cooldown' => 2, 'chance' => 100],
        ],
    ]);

    // Potwór rzucił ogłuszenie (tura z ccType).
    $stunCast = collect($turns)->first(fn ($t) => ($t['ccType'] ?? null) === 'stun' && $t['actor'] === 'enemy');
    expect($stunCast)->not->toBeNull();

    // Gracz stracił turę - jest tura gracza oznaczona crowd_controlled.
    $playerCc = collect($turns)->first(fn ($t) => $t['actor'] === 'player' && ($t['type'] ?? null) === 'crowd_controlled');
    expect($playerCc)->not->toBeNull();
});

test('monster with no abilities behaves exactly as before (no skill turns)', function () {
    $turns = makeSkillFight([]);

    $skillish = collect($turns)->first(fn ($t) =>
        !empty($t['isMagic']) || ($t['playerDotDamage'] ?? 0) > 0 || ($t['ccType'] ?? null) !== null
        || (($t['actor'] ?? null) === 'player' && ($t['type'] ?? null) === 'crowd_controlled')
    );
    expect($skillish)->toBeNull();
});
