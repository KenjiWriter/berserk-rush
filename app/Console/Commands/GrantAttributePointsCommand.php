<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Persistence\Character;

class GrantAttributePointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'character:grant-ap
                            {character : ID (ULID) postaci, ID (UID) użytkownika, nazwa postaci lub email}
                            {points : Ilość punktów atrybutów (Stat Points / AP) do dodania}';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'character:add-ap',
        'character:grant-stats',
        'character:add-stats',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Przyznaje punkty atrybutów (Stat/Attribute Points - STR, INT, VIT, AGI) dla postaci na podstawie jej ID (ULID), UID użytkownika, nazwy lub emaila.';

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
            $this->warn('Ilość punktów atrybutów do dodania wynosi 0. Brak zmian.');
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

        // 3. Szukanie po nazwie postaci (case-insensitive)
        if (!$character) {
            $character = Character::whereRaw('LOWER(name) = ?', [strtolower($identifier)])->first();
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

        $oldPoints = (int) ($character->character_points ?? 0);
        $newPoints = max(0, $oldPoints + $points);

        $character->update([
            'character_points' => $newPoints,
        ]);

        if (method_exists($character, 'clearStatsCache')) {
            $character->clearStatsCache();
        }

        $this->newLine();
        $this->info("SUKCES: Zaktualizowano punkty atrybutów (Attribute/Stat Points) dla postaci '{$character->name}'");
        $this->line(" - Postać ID (ULID): <comment>{$character->id}</comment>");
        $this->line(" - Użytkownik (UID / Email): <comment>" . ($character->user ? "{$character->user->id} ({$character->user->email})" : 'Brak') . "</comment>");
        $this->line(" - Poprzednia liczba AP (Atrybutów): <comment>{$oldPoints}</comment>");
        $this->line(" - Zmiana AP: " . ($points > 0 ? "<fg=green;options=bold>+{$points}</>" : "<fg=red;options=bold>{$points}</>"));
        $this->line(" - Nowa liczba AP (Atrybutów): <fg=green;options=bold>{$newPoints}</>");

        return Command::SUCCESS;
    }
}
