<?php

namespace App\Application\Characters;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;

class RewardService
{
    public function apply(Character $character, Encounter $encounter): Result
    {
        if ($encounter->result !== 'win') {
            return Result::error('NO_REWARDS', 'Brak nagród za przegraną walkę');
        }

        try {
            return DB::transaction(function () use ($character, $encounter) {
                $idempotencyKey = "encounter:{$encounter->id}";

                // Check if rewards already applied
                $existingEntry = CurrencyLedger::where('idempotency_key', $idempotencyKey)
                    ->where('character_id', $character->id)
                    ->first();

                if ($existingEntry) {
                    return Result::ok([
                        'gold' => $character->gold,
                        'xp' => $character->xp,
                        'already_applied' => true
                    ]);
                }

                // Apply gold reward
                if ($encounter->gold_reward > 0) {
                    $character->increment('gold', $encounter->gold_reward);

                    CurrencyLedger::create([
                        'id' => (string) \Illuminate\Support\Str::ulid(),
                        'character_id' => $character->id,
                        'currency_type' => 'gold',
                        'amount' => $encounter->gold_reward,
                        'balance_after' => $character->fresh()->gold,
                        'reason' => 'reward',
                        'ref_type' => 'encounter',
                        'ref_id' => $encounter->id,
                        'idempotency_key' => $idempotencyKey,
                    ]);
                }

                // Apply XP reward
                if ($encounter->xp_reward > 0) {
                    $character->increment('xp', $encounter->xp_reward);
                }

                $character->refresh();

                return Result::ok([
                    'gold' => $character->gold,
                    'xp' => $character->xp,
                    'gold_gained' => $encounter->gold_reward,
                    'xp_gained' => $encounter->xp_reward,
                    'already_applied' => false
                ]);
            });
        } catch (\Exception $e) {
            return Result::error('REWARD_APPLICATION_FAILED', 'Nie udało się zastosować nagród', [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
