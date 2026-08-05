<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Infrastructure\Persistence\CharacterLocationEventRun;
use App\Application\LocationEvents\LocationEventService;
use Illuminate\Support\Facades\Log;

class SimulateLocationEventStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $runId;

    public function __construct(int $runId)
    {
        $this->runId = $runId;
    }

    public function handle(LocationEventService $locationEventService): void
    {
        $run = CharacterLocationEventRun::find($this->runId);

        if (!$run || $run->combat_state !== 'calculating') {
            Log::warning('SimulateLocationEventStageJob: Run not found or not in calculating state', ['run_id' => $this->runId]);
            return;
        }

        Log::info('SimulateLocationEventStageJob: Simulating location event combat', ['run_id' => $this->runId]);

        $result = $locationEventService->simulateStage($run);

        if ($result->isError()) {
            Log::error('SimulateLocationEventStageJob: Simulation failed', [
                'run_id' => $this->runId,
                'error' => $result->getErrorMessage(),
            ]);

            $run->combat_state = 'error';
            $run->save();
        } else {
            $run->combat_data = $result->getPayload();
            $run->combat_state = 'completed';
            $run->save();
            Log::info('SimulateLocationEventStageJob: Simulation completed successfully', ['run_id' => $this->runId]);
        }
    }
}
