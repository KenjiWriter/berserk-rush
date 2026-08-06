<?php

/**
 * Ad-hoc balance calc for the new endgame map ("Epicentrum Apokalipsy", tier 9,
 * level 95-99). Logic 1:1 copied from BalanceMonstersCommand (buildArchetype/
 * scaleStat/classArmorBonusForLevel/simulateFight/monteCarlo/solveMonster) so the
 * numbers stay consistent with the game's real combat formulas, but with CUSTOM,
 * intentionally harsher difficulty targets for both 'regular' and 'boss' ranks to
 * produce a genuine difficulty spike vs Skazone Miasto (see targets below).
 *
 * Reference character convention kept identical to the existing calculator
 * (charLevel = map levelMin + 1 = 96, craft weapon tier = best available <= charLevel,
 * shop armor capped at its own max level 90) so the ONLY deliberate lever pulled here
 * is the difficulty target, not a different gear assumption - keeps this map's numbers
 * apples-to-apples comparable/derivable the same way as the other 8 maps.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Persistence\ItemInstance;

const ATTRIBUTE_DAMAGE_MULTIPLIER = 1.5;
const ATTRIBUTE_HP_MULTIPLIER = 15;
const EXPECTED_UPGRADE_LEVEL = 2;
const EXPECTED_ENCHANT_BONUS_PCT = 0.05;

$classArmorBonusByTierLevel = [
    5  => ['w' => ['str' => 7,   'vit' => 5],   'm' => ['int' => 12],  'a' => ['agi' => 12]],
    15 => ['w' => ['str' => 16,  'vit' => 19],  'm' => ['int' => 35],  'a' => ['agi' => 35]],
    25 => ['w' => ['str' => 30,  'vit' => 29],  'm' => ['int' => 59],  'a' => ['agi' => 59]],
    35 => ['w' => ['str' => 40,  'vit' => 42],  'm' => ['int' => 82],  'a' => ['agi' => 82]],
    45 => ['w' => ['str' => 53,  'vit' => 53],  'm' => ['int' => 106], 'a' => ['agi' => 106]],
    55 => ['w' => ['str' => 64,  'vit' => 65],  'm' => ['int' => 129], 'a' => ['agi' => 129]],
    65 => ['w' => ['str' => 76,  'vit' => 77],  'm' => ['int' => 153], 'a' => ['agi' => 153]],
    75 => ['w' => ['str' => 88,  'vit' => 88],  'm' => ['int' => 176], 'a' => ['agi' => 176]],
    85 => ['w' => ['str' => 100, 'vit' => 100], 'm' => ['int' => 200], 'a' => ['agi' => 200]],
    95 => ['w' => ['str' => 112, 'vit' => 112], 'm' => ['int' => 224], 'a' => ['agi' => 224]],
    99 => ['w' => ['str' => 116, 'vit' => 117], 'm' => ['int' => 233], 'a' => ['agi' => 233]],
];

$shopTiers = [
    0  => ['level' => 1,  'scale' => 1.2],
    1  => ['level' => 10, 'scale' => 1.44],
    2  => ['level' => 20, 'scale' => 1.73],
    3  => ['level' => 30, 'scale' => 2.07],
    4  => ['level' => 40, 'scale' => 2.49],
    5  => ['level' => 50, 'scale' => 2.99],
    7  => ['level' => 60, 'scale' => 3.58],
    8  => ['level' => 70, 'scale' => 4.30],
    9  => ['level' => 80, 'scale' => 5.16],
    10 => ['level' => 90, 'scale' => 6.19],
];

$craftWeaponProtos = [
    'sword'  => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 1],
    'axe'    => ['attack_min' => 0.75, 'attack_max' => 7.5],
    'bow'    => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 3],
    'wand'   => ['magic_attack_min' => 3, 'magic_attack_max' => 6.75, 'crit_chance' => 1],
    'dagger' => ['attack_min' => 1.2, 'attack_max' => 4.8, 'crit_chance' => 8],
    'bell'   => ['attack_min' => 1.8, 'attack_max' => 3.6, 'magic_burst_chance' => 30, 'magic_burst_min' => 3, 'magic_burst_max' => 6.75],
];

$armorProtos = [
    'armor'  => ['defense' => 4, 'hp_bonus' => 13.5],
    'helmet' => ['defense' => 2, 'hp_bonus' => 7.5],
    'boots'  => ['defense' => 1, 'hp_bonus' => 4.5],
    'amulet' => ['defense' => 1, 'hp_bonus' => 11.25, 'mana_bonus' => 10],
    'ring'   => ['crit_chance' => 2, 'hp_bonus' => 6],
];

$craftTiers = [
    0  => ['level' => 5,  'scale' => 2.0],
    1  => ['level' => 15, 'scale' => 2.4],
    2  => ['level' => 25, 'scale' => 2.88],
    3  => ['level' => 35, 'scale' => 3.46],
    4  => ['level' => 45, 'scale' => 4.15],
    5  => ['level' => 55, 'scale' => 4.98],
    6  => ['level' => 65, 'scale' => 5.97],
    7  => ['level' => 75, 'scale' => 7.17],
    8  => ['level' => 85, 'scale' => 8.60],
    9  => ['level' => 95, 'scale' => 10.32],
    10 => ['level' => 99, 'scale' => 12.38],
];

function pickTierIndex(array $tiers, int $charLevel): int {
    $best = 0;
    foreach ($tiers as $idx => $tier) {
        if ($tier['level'] <= $charLevel && $tier['level'] >= $tiers[$best]['level']) {
            $best = $idx;
        }
    }
    return $best;
}

function scaleStat(string $stat, float $base, float $scale, int $tierIndex, float $critCap, float $burstCap, float $gearMultiplier): float {
    if ($stat === 'crit_chance') return min($critCap, $base + $tierIndex * 0.5);
    if ($stat === 'magic_burst_chance') return min($burstCap, $base + $tierIndex * 2);
    return $base * $scale * $gearMultiplier;
}

function classArmorBonusForLevel(array $table, int $level, string $weaponType): array {
    if ($level < 5) return [];
    $classKey = match ($weaponType) {
        'sword', 'axe', 'bow' => 'w',
        'wand', 'bell' => 'm',
        'dagger' => 'a',
        default => 'w',
    };
    $best = 5;
    foreach (array_keys($table) as $tierLevel) {
        if ($tierLevel <= $level && $tierLevel >= $best) $best = $tierLevel;
    }
    return $table[$best][$classKey] ?? [];
}

function buildArchetype(
    string $weaponType, int $level, array $weaponProto, float $weaponScale, int $weaponTierIndex,
    float $weaponCritCap, float $weaponBurstCap, float $armorScale, int $armorTierIndex,
    array $armorProtos, array $classArmorBonusByTierLevel
): array {
    $eq = [
        'attack_min' => 0, 'attack_max' => 0, 'magic_attack_min' => 0, 'magic_attack_max' => 0,
        'defense' => 0, 'hp_bonus' => 0, 'mana_bonus' => 0, 'crit_chance' => 0,
        'magic_burst_chance' => 0, 'magic_burst_min' => 0, 'magic_burst_max' => 0,
    ];

    $upgradeBonusPct = ItemInstance::UPGRADE_BONUS_PERCENT_BY_LEVEL[EXPECTED_UPGRADE_LEVEL] ?? 0;
    $gearMultiplier = 1 + ($upgradeBonusPct / 100) + EXPECTED_ENCHANT_BONUS_PCT;

    foreach ($weaponProto as $stat => $base) {
        $eq[$stat] += scaleStat($stat, $base, $weaponScale, $weaponTierIndex, $weaponCritCap, $weaponBurstCap, $gearMultiplier);
    }
    foreach (['armor', 'helmet', 'boots', 'amulet', 'ring'] as $piece) {
        foreach ($armorProtos[$piece] as $stat => $base) {
            $eq[$stat] += scaleStat($stat, $base, $armorScale, $armorTierIndex, 15, 60, $gearMultiplier);
        }
    }

    $attrPoints = 10 + max(0, ($level - 1) * 3);
    if ($weaponType === 'wand') {
        $int = (int) round($attrPoints * 0.4); $agi = (int) round($attrPoints * 0.3); $str = 0;
    } else {
        $str = (int) round($attrPoints * 0.4); $agi = (int) round($attrPoints * 0.3); $int = 0;
    }
    $vit = max(0, $attrPoints - $str - $agi - $int);

    $classBonus = classArmorBonusForLevel($classArmorBonusByTierLevel, $level, $weaponType);
    $str += $classBonus['str'] ?? 0; $vit += $classBonus['vit'] ?? 0;
    $int += $classBonus['int'] ?? 0; $agi += $classBonus['agi'] ?? 0;

    if ($weaponType === 'wand') {
        $rawStatBonus = $int * 2;
        $weaponAtkMin = $eq['magic_attack_min']; $weaponAtkMax = max($weaponAtkMin, $eq['magic_attack_max']);
    } elseif ($weaponType === 'bell') {
        $rawStatBonus = $str + $int;
        $weaponAtkMin = $eq['attack_min']; $weaponAtkMax = max($weaponAtkMin, $eq['attack_max']);
    } else {
        $rawStatBonus = match ($weaponType) {
            'bow', 'sword', 'dagger' => $str + $agi,
            'axe' => $str * 2,
            default => $str * 2,
        };
        $weaponAtkMin = $eq['attack_min']; $weaponAtkMax = max($weaponAtkMin, $eq['attack_max']);
    }
    $statBonus = (int) round($rawStatBonus * ATTRIBUTE_DAMAGE_MULTIPLIER);

    $baseFlat = 10 + $statBonus + $level;
    $baseDamageMin = $baseFlat + $weaponAtkMin;
    $baseDamageMax = max($baseDamageMin, $baseFlat + $weaponAtkMax);

    $maxHp = 100 + $vit * ATTRIBUTE_HP_MULTIPLIER + $level * 5 + $eq['hp_bonus'];
    $playerDefBase = $vit + ($level / 2) + $eq['defense'];

    return [
        'weaponType' => $weaponType, 'str' => $str, 'int' => $int, 'agi' => $agi, 'vit' => $vit,
        'baseDamageMin' => $baseDamageMin, 'baseDamageMax' => $baseDamageMax,
        'maxHp' => (int) round($maxHp), 'playerDefBase' => $playerDefBase,
        'critGearPct' => $eq['crit_chance'], 'magicBurstChance' => $eq['magic_burst_chance'],
        'magicBurstMin' => $eq['magic_burst_min'], 'magicBurstMax' => $eq['magic_burst_max'],
    ];
}

function simulateFight(array $a, array $monster): array {
    $playerHp = $a['maxHp']; $monsterHp = $monster['hp'];
    $playerAgi = $a['agi']; $monsterAgi = $monster['agi'];
    $playerFirst = $playerAgi >= $monsterAgi;
    $landedHits = 0; $dmgTaken = 0; $turn = 0;

    while ($playerHp > 0 && $monsterHp > 0 && $turn < 50) {
        $isPlayerTurn = $playerFirst ? ($turn % 2 === 0) : ($turn % 2 === 1);
        if ($isPlayerTurn) {
            $roll = mt_rand((int) round($a['baseDamageMin'] * 100), (int) round($a['baseDamageMax'] * 100)) / 100;
            $baseDmg = max(1, $roll - $monster['def'] * 0.2);
            $magicBurstDmg = 0;
            if ($a['magicBurstChance'] > 0 && (mt_rand(1, 10000) / 100) <= $a['magicBurstChance']) {
                $burstRoll = $a['magicBurstMax'] > $a['magicBurstMin']
                    ? mt_rand((int) round($a['magicBurstMin'] * 100), (int) round($a['magicBurstMax'] * 100)) / 100
                    : $a['magicBurstMin'];
                $magicBurstDmg = max(1, round($burstRoll - $monster['def'] * 0.2));
            }
            $totalDmg = $baseDmg + $magicBurstDmg;
            $critChance = max(0.03, (0.05 + $playerAgi * 0.004 + $a['critGearPct'] / 100));
            $isCrit = (mt_rand(1, 1000000) / 1000000) < $critChance;
            $missChance = min(0.30, 0.03 + $monsterAgi * 0.0006);
            $isMiss = (mt_rand(1, 1000000) / 1000000) < $missChance;
            if ($isMiss) { $dmgDealt = 0; } else {
                if ($isCrit) $totalDmg = (int) ($totalDmg * 1.5);
                $dmgDealt = (int) $totalDmg; $landedHits++;
            }
            $monsterHp = max(0, $monsterHp - $dmgDealt);
        } else {
            $rawDmg = $monster['atk'];
            $dmg = max(1, $rawDmg - $a['playerDefBase'] * 0.2);
            $monsterCrit = max(0.02, min(0.30, 0.03 + $monsterAgi * 0.003));
            $isCrit = (mt_rand(1, 1000000) / 1000000) < $monsterCrit;
            $monsterMiss = min(0.30, 0.03 + $playerAgi * 0.0006);
            $isMiss = (mt_rand(1, 1000000) / 1000000) < $monsterMiss;
            if ($isMiss) { $dmgDealt = 0; } else {
                if ($isCrit) $dmg = (int) ($dmg * 1.5);
                $dmgDealt = (int) $dmg;
            }
            $playerHp = max(0, $playerHp - $dmgDealt);
            $dmgTaken += $dmgDealt;
        }
        $turn++;
    }
    return ['win' => $monsterHp <= 0 && $playerHp > 0, 'hits' => $landedHits, 'dmgTaken' => $dmgTaken, 'maxHp' => $a['maxHp']];
}

function monteCarlo(array $a, array $monster, int $n): array {
    $wins = 0; $hitsSum = 0; $dmgFractionSum = 0;
    for ($i = 0; $i < $n; $i++) {
        $r = simulateFight($a, $monster);
        if ($r['win']) $wins++;
        $hitsSum += $r['hits'];
        $dmgFractionSum += $r['dmgTaken'] / max(1, $r['maxHp']);
    }
    return ['winRate' => $wins / $n, 'avgHits' => $hitsSum / $n, 'avgDmgFraction' => $dmgFractionSum / $n];
}

function solveMonster(array $archetypes, int $iterations, int $tuneSims, int $verifySims, float $targetWinrate, float $targetHitsMid, float $targetDmgFraction): array {
    $avgPlayerAgi = array_sum(array_column($archetypes, 'agi')) / count($archetypes);
    $avgRawDmg = array_sum(array_map(fn ($a) => ($a['baseDamageMin'] + $a['baseDamageMax']) / 2, $archetypes)) / count($archetypes);
    $avgMaxHp = array_sum(array_column($archetypes, 'maxHp')) / count($archetypes);
    $avgPlayerDef = array_sum(array_column($archetypes, 'playerDefBase')) / count($archetypes);

    $monster = ['agi' => max(1, (int) round($avgPlayerAgi * 0.75)), 'def' => max(0, (int) round($avgRawDmg * 0.15))];
    $monster['hp'] = max(10, (int) round(max(1, $avgRawDmg - $monster['def'] * 0.2) * $targetHitsMid));
    $perHitDmgTarget = ($targetDmgFraction * $avgMaxHp) / $targetHitsMid;
    $monster['atk'] = max(1, (int) round($perHitDmgTarget + $avgPlayerDef * 0.2));

    for ($iter = 0; $iter < $iterations; $iter++) {
        $agg = ['winRate' => 0, 'avgHits' => 0, 'avgDmgFraction' => 0];
        foreach ($archetypes as $a) {
            $r = monteCarlo($a, $monster, $tuneSims);
            $agg['winRate'] += $r['winRate']; $agg['avgHits'] += $r['avgHits']; $agg['avgDmgFraction'] += $r['avgDmgFraction'];
        }
        $n = count($archetypes);
        $agg['winRate'] /= $n; $agg['avgHits'] /= $n; $agg['avgDmgFraction'] /= $n;

        $hitsRatio = $targetHitsMid / max(0.5, $agg['avgHits']);
        $monster['hp'] = max(5, (int) round($monster['hp'] * pow($hitsRatio, 0.6)));
        $dmgRatio = $targetDmgFraction / max(0.05, $agg['avgDmgFraction']);
        $monster['atk'] = max(1, (int) round($monster['atk'] * pow($dmgRatio, 0.6)));

        if ($agg['winRate'] < $targetWinrate) {
            $monster['atk'] = max(1, (int) round($monster['atk'] * 0.96));
            $monster['hp'] = max(5, (int) round($monster['hp'] * 0.99));
        }
    }

    $verification = [];
    foreach ($archetypes as $weaponType => $a) {
        $verification[$weaponType] = monteCarlo($a, $monster, $verifySims);
    }
    return [$monster, $verification];
}

// ==== Reference character: level 96 (map levelMin 95 + 1), same convention as the
// other 8 maps in BalanceMonstersCommand ====
$charLevel = 96;
$armorTierIndex = pickTierIndex($shopTiers, $charLevel);
$armorTier = $shopTiers[$armorTierIndex];
$weaponTierIndex = pickTierIndex($craftTiers, $charLevel);
$weaponTier = $craftTiers[$weaponTierIndex];

echo "charLevel={$charLevel} armorTier(lvl{$armorTier['level']} x{$armorTier['scale']}) weaponTier(lvl{$weaponTier['level']} x{$weaponTier['scale']})\n\n";

$archetypes = [];
foreach (array_keys($craftWeaponProtos) as $weaponType) {
    $archetypes[$weaponType] = buildArchetype(
        $weaponType, $charLevel, $craftWeaponProtos[$weaponType], $weaponTier['scale'], $weaponTierIndex,
        50, 70, $armorTier['scale'], $armorTierIndex, $armorProtos, $classArmorBonusByTierLevel
    );
}

foreach ($archetypes as $w => $a) {
    printf("%-8s STR=%-4d INT=%-4d AGI=%-4d VIT=%-4d dmg=%.1f-%.1f maxHp=%d\n", $w, $a['str'], $a['int'], $a['agi'], $a['vit'], $a['baseDamageMin'], $a['baseDamageMax'], $a['maxHp']);
}

$avgPlayerAgi = array_sum(array_column($archetypes, 'agi')) / count($archetypes);
$avgRawDmg = array_sum(array_map(fn ($a) => ($a['baseDamageMin'] + $a['baseDamageMax']) / 2, $archetypes)) / count($archetypes);
$avgMaxHp = array_sum(array_column($archetypes, 'maxHp')) / count($archetypes);
$avgPlayerDef = array_sum(array_column($archetypes, 'playerDefBase')) / count($archetypes);
printf("\navgRawDmg=%.1f avgMaxHp=%.1f avgPlayerDef=%.1f avgPlayerAgi=%.1f\n", $avgRawDmg, $avgMaxHp, $avgPlayerDef, $avgPlayerAgi);

// ==== Custom, harsher difficulty targets (deliberate "significant jump" beyond the
// standard 'regular' target of 90%/3.5hits/60%dmg) ====
$targets = [
    'regular' => ['winrate' => 0.80, 'hits' => 6.0, 'dmgFrac' => 0.75],
    'boss_a'  => ['winrate' => 0.55, 'hits' => 14.0, 'dmgFrac' => 0.95],
    'boss_b'  => ['winrate' => 0.45, 'hits' => 18.0, 'dmgFrac' => 0.97],
];

foreach ($targets as $label => $t) {
    echo "\n=== {$label} (target winrate={$t['winrate']}, hits={$t['hits']}, dmgFrac={$t['dmgFrac']}) ===\n";
    [$monster, $verification] = solveMonster($archetypes, 30, 4000, 8000, $t['winrate'], $t['hits'], $t['dmgFrac']);
    echo "SOLVED: hp={$monster['hp']} atk={$monster['atk']} def={$monster['def']} agi={$monster['agi']}\n";
    foreach ($verification as $w => $v) {
        printf("  %-8s winrate=%.1f%% avgHits=%.2f avgDmgFrac=%.1f%%\n", $w, $v['winRate'] * 100, $v['avgHits'], $v['avgDmgFraction'] * 100);
    }
}

// ==== Hand-picked final candidates (grounded in avgRawDmg/avgMaxHp above, avoiding
// the boss-target solver's known-unreliable interaction between hits/dmgFrac for
// high hit counts - see docs/rebalance_2026_08_progress.md "Ranga boss CELOWO
// nie ruszona"). Ratios anchored to Skazone Miasto's own live boss:regular ratio
// (~4.6x/1.41x HP/ATK for its first boss, ~5.3x/1.58x for its second) pushed higher
// for the intended extra difficulty spike. ====
echo "\n\n==== HAND-PICKED CANDIDATES ====\n";
$candidates = [
    'REGULAR (avg)' => ['hp' => 5501, 'atk' => 377, 'def' => 118, 'agi' => 95],
    'BOSS A'        => ['hp' => 13500, 'atk' => 222, 'def' => 133, 'agi' => 103],
    'BOSS B (final)'=> ['hp' => 14200, 'atk' => 226, 'def' => 136, 'agi' => 107],
];
foreach ($candidates as $label => $monster) {
    echo "\n-- {$label}: hp={$monster['hp']} atk={$monster['atk']} def={$monster['def']} agi={$monster['agi']} --\n";
    foreach ($archetypes as $w => $a) {
        $v = monteCarlo($a, $monster, 8000);
        printf("  %-8s winrate=%.1f%% avgHits=%.2f avgDmgFrac=%.1f%%\n", $w, $v['winRate'] * 100, $v['avgHits'], $v['avgDmgFraction'] * 100);
    }
}
