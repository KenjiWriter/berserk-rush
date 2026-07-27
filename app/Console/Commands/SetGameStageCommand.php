<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Persistence\Character;

class SetGameStageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'character:set-stage
                            {character : ID (ULID) postaci, ID (UID) użytkownika, nazwa postaci lub email}
                            {stage : Nowy numer Game Stage (np. 0, 10, 22, 23)}';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'user:set-stage',
        'character:set-gamestage',
        'game:set-stage',
        'set-stage',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ustawia dany numer Game Stage dla wybranego użytkownika / postaci.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = trim((string) $this->argument('character'));
        $newStage = (int) $this->argument('stage');

        if ($newStage < 0) {
            $this->error('Game Stage nie może być mniejszy niż 0.');
            return Command::FAILURE;
        }

        $character = null;
        $user = null;

        // 1. Szukanie po ULID/ID postaci
        $character = Character::find($identifier);

        // 2. Szukanie Usera po ID (UID)
        if (!$character && is_numeric($identifier)) {
            $user = User::find((int) $identifier);
            if ($user) {
                $character = $user->characters()->first();
            }
        }

        // 3. Szukanie po nazwie postaci (case-insensitive)
        if (!$character) {
            $character = Character::whereRaw('LOWER(name) = ?', [strtolower($identifier)])->first();
        }

        // 4. Szukanie Usera po Emailu lub Nazwie Użytkownika
        if (!$character && !$user) {
            $user = User::where('email', $identifier)->orWhere('name', $identifier)->first();
            if ($user) {
                $character = $user->characters()->first();
            }
        }

        if ($character) {
            $user = $character->user;
        }

        if (!$user) {
            $this->error("Nie znaleziono użytkownika ani postaci dla identyfikatora: '{$identifier}'.");
            return Command::FAILURE;
        }

        $oldStage = $user->game_stage;
        $user->game_stage = $newStage;
        $user->save();

        $this->newLine();
        $this->info("SUKCES: Zaktualizowano Game Stage konta!");
        $this->line(" - Użytkownik (UID / Email): <comment>{$user->id} ({$user->email})</comment>");
        if ($character) {
            $this->line(" - Powiązana postać      : <comment>{$character->name} (ULID: {$character->id})</comment>");
        }
        $this->line(" - Poprzedni Game Stage   : <comment>{$oldStage}</comment>");
        $this->line(" - Nowy Game Stage        : <fg=green;options=bold>{$user->game_stage}</>");
        $this->newLine();

        return Command::SUCCESS;
    }
}
