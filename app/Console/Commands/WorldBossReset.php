<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Combat\WorldBossService;

class WorldBossReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:world-boss-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instantly resets all active World Bosses and spawns new instances with updated stats.';

    /**
     * Execute the console command.
     */
    public function handle(WorldBossService $service)
    {
        $this->info('Resetting all World Bosses...');
        $service->resetBosses();
        $this->info('World Bosses reset completed successfully!');
    }
}
