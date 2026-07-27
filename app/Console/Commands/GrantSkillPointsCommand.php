<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Persistence\Character;

class GrantSkillPointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'character:grant-sp
                            {character : ID (ULID) postaci, ID (UID) użytkownika, nazwa postaci lub email}
                            {points : Ilość punktów umiejętności (Skill Points) do dodania}';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'character:add-sp',
        'character:add-skill-points',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Przyznaje punkty umiejętności (Skill Points) dla postaci na podstawie jej ID (ULID), UID użytkownika, nazwy lub emaila.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = trim((string) $this->argument('character'));
        $points = (int) $this->argument('points');

        if (empty($identifier)) {
            $this->error('Musisz podać identyfikator postaci lub użytkownika (ULID, UID, nazwa, email).');
            return Command::FAILURE;
        }

        if ($points === 0) {
            $this->warn('Ilość punktów umiejętności do dodania wynosi 0. Brak zmian.');
            return Command::SUCCESS;
        }

        $character = null;

        // 1. Szukanie bezpośrednio po ULID/ID postaci
        $character = Character::find($identifier);

        // 2. Jeśli numeryczne - szukanie Usera po ID (UID) i pobranie jego pierwszej postaci
        if (!$character && is_numeric($identifier)) {
            $user = User::find((int) $identifier);
            if ($user) {
                $character = $user->characters()->first();
                if ($character) {
                    $this->info("Znaleziono użytkownika UID {$user->id} ({$user->email}), wybrano jego postać: {$character->name}");
                } else {
                    $this->error("Użytkownik o UID {$identifier} nie posiada żadnej postaci.");
                    return Command::FAILURE;
                }
            }
        }

        // 3. Szukanie po nazwie postaci
        if (!$character) {
            $character = Character::where('name', $identifier)->first();
        }

        // 4. Szukanie Usera po Emailu
        if (!$character) {
            $user = User::where('email', $identifier)->first();
            if ($user) {
                $character = $user->characters()->first();
            }
        }

        if (!$character) {
            $this->error("Nie znaleziono postaci ani użytkownika dla identyfikatora: '{$identifier}'.");
            return Command::FAILURE;
        }

        $oldSkillPoints = (int) ($character->skill_points ?? 0);
        $newSkillPoints = max(0, $oldSkillPoints + $points);

        $character->update([
            'skill_points' => $newSkillPoints,
        ]);

        if (method_exists($character, 'clearStatsCache')) {
            $character->clearStatsCache();
        }

        $this->newLine();
        $this->info("SUKCES: Zaktualizowano punkty umiejętności (Skill Points) dla postaci '{$character->name}'");
        $this->line(" - Postać ID (ULID): <comment>{$character->id}</comment>");
        $this->line(" - Użytkownik (UID / Email): <comment>" . ($character->user ? "{$character->user->id} ({$character->user->email})" : 'Brak') . "</comment>");
        $this->line(" - Poprzednia liczba SP: <comment>{$oldSkillPoints}</comment>");
        $this->line(" - Zmiana SP: " . ($points > 0 ? "<fg=green;options=bold>+{$points}</>" : "<fg=red;options=bold>{$points}</>"));
        $this->line(" - Nowa liczba SP: <fg=green;options=bold>{$newSkillPoints}</>");

        return Command::SUCCESS;
    }
}
