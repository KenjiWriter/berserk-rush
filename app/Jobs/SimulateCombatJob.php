<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Infrastructure\Persistence\Encounter;
use App\Application\Combat\EncounterService;
use Illuminate\Support\Facades\Log;

class SimulateCombatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $encounterId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $encounterId)
    {
        $this->encounterId = $encounterId;
    }

    /**
     * Execute the job.
     */
    public function handle(EncounterService $encounterService): void
    {
        $encounter = Encounter::find($this->encounterId);

        if (!$encounter || $encounter->state !== 'ongoing') {
            Log::warning('SimulateCombatJob: Encounter not found or already completed', ['encounter_id' => $this->encounterId]);
            return;
        }

        Log::info('SimulateCombatJob: Simulating combat', ['encounter_id' => $this->encounterId]);

        $result = $encounterService->simulate($encounter);

        if ($result->isError()) {
            Log::error('SimulateCombatJob: Simulation failed', [
                'encounter_id' => $this->encounterId,
                'error' => $result->getErrorMessage()
            ]);
            
            // Mark as error so the UI doesn't hang forever
            $encounter->state = 'error';
            $encounter->save();
        } else {
            Log::info('SimulateCombatJob: Simulation completed successfully', ['encounter_id' => $this->encounterId]);
        }
    }
}
