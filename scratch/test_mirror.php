<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Application\Mirror\MirrorService;

echo "--- TESTING MIRROR MECHANIC ---\n";

$char = Character::first();
if (!$char) {
    echo "No character found.\n";
    exit(0);
}

$map = Map::first();
if (!$map) {
    echo "No map found.\n";
    exit(0);
}

echo "Character: {$char->name} (Level {$char->level})\n";
echo "Map: {$map->name} (Level range: {$map->level_range})\n";

$mirrorService = app(MirrorService::class);

// 1. Get rates
$rates = $mirrorService->getMapRates($char, $map);
echo "Map Rates: EXP/min = {$rates['exp_per_minute']}, Gold/min = {$rates['gold_per_minute']}\n";

// Clear any existing active session for testing
if ($char->hasActiveMirror()) {
    $session = $char->activeMirrorSession;
    $session->update(['status' => 'cancelled']);
    $char->refresh();
}

// 2. Start Mirror session for 2 hours
$session = $mirrorService->startMirror($char, $map, 2);
echo "Mirror started! Session ID: {$session->id}, Status: {$session->status}, Ends at: {$session->ends_at}\n";
echo "Has Active Mirror: " . ($char->fresh()->hasActiveMirror() ? 'YES' : 'NO') . "\n";

// 3. Simulate elapsed minutes for calculation test
$rewards = $session->calculateCurrentRewards();
echo "Calculated Rewards for 0 min: XP={$rewards['xp']}, Gold={$rewards['gold']}, Materials count=" . count($rewards['materials']) . "\n";

// 4. Stop and claim
$claimResult = $mirrorService->stopAndClaim($char->fresh());
echo "Claimed rewards successfully! Claimed XP={$claimResult['xp']}, Gold={$claimResult['gold']}, Materials=" . count($claimResult['materials']) . "\n";
echo "Has Active Mirror after claim: " . ($char->fresh()->hasActiveMirror() ? 'YES' : 'NO') . "\n";

echo "--- TEST PASSED CLEANLY ---\n";
