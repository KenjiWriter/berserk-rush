<?php

namespace App\Jobs;

use App\Application\PvP\PvPEncounterService;
use App\Infrastructure\Persistence\Mail;
use App\Infrastructure\Persistence\PvpEncounter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimulatePvPEncounterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $pvpEncounterId;

    public function __construct(string $pvpEncounterId)
    {
        $this->pvpEncounterId = $pvpEncounterId;
    }

    public function handle(PvPEncounterService $service): void
    {
        $encounter = PvpEncounter::find($this->pvpEncounterId);

        if (!$encounter) {
            Log::warning('PvP encounter not found for simulation', ['id' => $this->pvpEncounterId]);
            return;
        }

        if ($encounter->state !== 'calculating') {
            Log::warning('PvP encounter not in calculating state', ['id' => $this->pvpEncounterId, 'state' => $encounter->state]);
            return;
        }

        $result = $service->simulate($encounter);

        if ($result->isSuccess()) {
            // Send mail to defender
            $attackerName = $encounter->attacker_snapshot['name'] ?? 'Nieznany gracz';
            $isDefenderWinner = ($result->getValue()['winner_id'] === $encounter->defender_character_id);
            $eloChange = $result->getValue()['defender_elo_change'];
            
            $subject = $isDefenderWinner ? '🛡️ Obroniono atak na Arenie!' : '⚔️ Przegrano obronę na Arenie';
            $body = $isDefenderWinner 
                ? "Gracz {$attackerName} zaatakował Cię na Arenie, ale udało Ci się obronić! Zmiana ELO: " . ($eloChange >= 0 ? "+{$eloChange}" : $eloChange)
                : "Gracz {$attackerName} pokonał Cię na Arenie. Zmiana ELO: {$eloChange}";

            Mail::create([
                'to_character_id' => $encounter->defender_character_id,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [],
            ]);
        } else {
            $encounter->update(['state' => 'error']);
            Log::error('PvP simulation returned error', ['id' => $this->pvpEncounterId, 'error' => $result->getError()]);
        }
    }
}
