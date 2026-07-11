<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Application\Dungeon\DungeonService;
use Illuminate\Support\Facades\Log;

class SimulateDungeonStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $runId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $runId)
    {
        $this->runId = $runId;
    }

    /**
     * Execute the job.
     */
    public function handle(DungeonService $dungeonService): void
    {
        $run = CharacterDungeonRun::find($this->runId);

        if (!$run || $run->combat_state !== 'calculating') {
            Log::warning('SimulateDungeonStageJob: Run not found or not in calculating state', ['run_id' => $this->runId]);
            return;
        }

        Log::info('SimulateDungeonStageJob: Simulating dungeon combat', ['run_id' => $this->runId]);

        // Wywołujemy rzeczywistą symulację w serwisie
        $result = $dungeonService->simulateStage($run);

        if ($result->isError()) {
            Log::error('SimulateDungeonStageJob: Simulation failed', [
                'run_id' => $this->runId,
                'error' => $result->getErrorMessage()
            ]);
            
            $run->combat_state = 'error';
            $run->save();
        } else {
            // Zapisz wynik walki do jsona
            $run->combat_data = $result->getPayload();
            $run->combat_state = 'completed';
            $run->save();
            Log::info('SimulateDungeonStageJob: Simulation completed successfully', ['run_id' => $this->runId]);
        }
    }
}
