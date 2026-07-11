<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Combat\WorldBossService;

class WorldBossTick extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:world-boss-tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handles hourly World Boss resets and reward distributions.';

    /**
     * Execute the console command.
     */
    public function handle(WorldBossService $service)
    {
        $this->info('Running World Boss hourly tick...');
        $service->tickHourly();
        $this->info('World Boss tick completed.');
    }
}

