<?php

namespace App\Application\PvP;

use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    public function findOpponents(Character $character, int $limit = 5): array
    {
        $baseElo = $character->elo;
        $tolerance = 0.10; // 10%
        $maxTolerance = 0.50; // 50%
        
        $opponents = [];
        
        // Find users that are already pending a challenge from this character
        $alreadyChallengedIds = DB::table('pvp_encounters')
            ->where('attacker_character_id', $character->id)
            ->whereIn('state', ['pending', 'calculating'])
            ->pluck('defender_character_id')
            ->toArray();
            
        $excludedIds = array_merge([$character->id], $alreadyChallengedIds);

        while ($tolerance <= $maxTolerance) {
            $minElo = $baseElo * (1 - $tolerance);
            $maxElo = $baseElo * (1 + $tolerance);
            
            $query = Character::where('id', '!=', $character->id)
                ->whereNotIn('id', $excludedIds)
                ->whereBetween('elo', [$minElo, $maxElo])
                ->inRandomOrder()
                ->limit($limit - count($opponents));
                
            $found = $query->get();
            
            foreach ($found as $opp) {
                $opponents[] = $opp;
                $excludedIds[] = $opp->id; // don't find them again
            }
            
            if (count($opponents) >= $limit) {
                break;
            }
            
            $tolerance += 0.05; // expand by 5%
        }
        
        return $opponents;
    }

    public function canRefresh(Character $character): bool
    {
        $this->checkReset($character);
        return $character->pvp_refreshes_used < 3;
    }

    public function useRefresh(Character $character): bool
    {
        $this->checkReset($character);
        
        if ($character->pvp_refreshes_used >= 3) {
            return false;
        }
        
        $updates = ['pvp_refreshes_used' => $character->pvp_refreshes_used + 1];
        if (!$character->pvp_refreshes_reset_at) {
            $updates['pvp_refreshes_reset_at'] = now()->addHour();
        }
        
        $character->update($updates);
        return true;
    }
    
    private function checkReset(Character $character): void
    {
        if ($character->pvp_refreshes_reset_at && now()->isAfter($character->pvp_refreshes_reset_at)) {
            $character->update([
                'pvp_refreshes_used' => 0,
                'pvp_refreshes_reset_at' => null
            ]);
        }
    }
}
