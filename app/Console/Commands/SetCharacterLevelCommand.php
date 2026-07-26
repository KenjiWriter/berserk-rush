<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Persistence\Character;

class SetCharacterLevelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'character:set-level {character : ID (ULID) lub nazwa postaci} {level : Nowy poziom (1-99)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ustawia dany poziom wybranej postaci na podstawie jej ID (ULID) lub nazwy oraz przelicza punkty postaci i umiejętności.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = trim($this->argument('character'));
        $newLevel = (int) $this->argument('level');

        if ($newLevel < 1 || $newLevel > 99) {
            $this->error('Poziom musi mieścić się w przedziale 1 - 99.');
            return Command::FAILURE;
        }

        // Szukanie postaci po ID lub po nazwie
        $character = Character::where('id', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if (!$character) {
            $this->error("Nie znaleziono postaci o identyfikatorze lub nazwie: '{$identifier}'.");
            return Command::FAILURE;
        }

        $oldLevel = $character->level;

        $character->update([
            'level' => $newLevel,
            'xp' => 0,
        ]);

        $character->refresh();
        $character->syncMissingPoints();
        $character->clearStatsCache();

        $this->info("Pomyślnie zmieniono poziom postaci '{$character->name}' (ID: {$character->id}).");
        $this->line(" - Stary poziom: {$oldLevel}");
        $this->line(" - Nowy poziom: {$character->level}");
        $this->line(" - Punkty atrybutów: {$character->character_points}");
        $this->line(" - Punkty umiejętności: {$character->skill_points}");

        return Command::SUCCESS;
    }
}
