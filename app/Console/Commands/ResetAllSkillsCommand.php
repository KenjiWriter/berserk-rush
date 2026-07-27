<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use Illuminate\Support\Facades\DB;

class ResetAllSkillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skills:reset-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetuje wszystkie odblokowane umiejętności u wszystkich postaci i przyznaje należne Punkty Umiejętności równe (poziom - 1) * 3.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Rozpoczynanie resetowania umiejętności dla wszystkich postaci...');

        DB::transaction(function () {
            // Delete all character combat skills
            CharacterCombatSkill::query()->delete();

            // Set skill_points = max(0, (level - 1) * 3) for all characters
            $characters = Character::all();
            $count = 0;

            foreach ($characters as $character) {
                $expectedPoints = max(0, ($character->level - 1) * 3);
                $character->update([
                    'skill_points' => $expectedPoints,
                ]);
                $count++;
            }

            $this->info("Pomyślnie zresetowano umiejętności i przeliczono Punkty Umiejętności dla {$count} postaci.");
        });

        return Command::SUCCESS;
    }
}
