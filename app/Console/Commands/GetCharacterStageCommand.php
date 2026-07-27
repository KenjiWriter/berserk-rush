<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Persistence\Character;

class GetCharacterStageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'character:stage
                            {character : ID (ULID) postaci, ID (UID) użytkownika, nazwa postaci lub email}';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'character:get-stage',
        'character:check-stage',
        'character:show-stage',
        'user:stage',
        'game:stage',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wyświetla aktualny Game Stage konta oraz szczegóły wybranej postaci lub użytkownika.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = trim((string) $this->argument('character'));

        if (empty($identifier)) {
            $this->error('Musisz podać identyfikator postaci lub użytkownika (ULID, UID, nazwa postaci lub email).');
            return Command::FAILURE;
        }

        $character = null;
        $user = null;

        // 1. Szukanie bezpośrednio po ULID/ID postaci
        $character = Character::find($identifier);

        // 2. Jeśli numeryczne - szukanie Usera po ID (UID)
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

        if (!$character && !$user) {
            $this->error("Nie znaleziono postaci ani użytkownika dla identyfikatora: '{$identifier}'.");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("==================================================");
        $this->info("             INFORMACJE O GAME STAGE              ");
        $this->info("==================================================");

        if ($user) {
            $this->line(" <fg=yellow;options=bold>KONTO UŻYTKOWNIKA:</>");
            $this->line("   - User ID (UID) : <comment>{$user->id}</comment>");
            $this->line("   - Nazwa użytk.  : <comment>{$user->name}</comment>");
            $this->line("   - Email         : <comment>{$user->email}</comment>");
            $this->line("   - Game Stage    : <fg=green;options=bold>{$user->game_stage}</>");
            $this->line("   - Uprawnienia   : <comment>{$user->permission_level}</comment>");
        }

        if ($character) {
            $this->newLine();
            $this->line(" <fg=yellow;options=bold>GŁÓWNA / ZNALEZIONA POSTAĆ:</>");
            $this->line("   - Nazwa postaci : <fg=cyan;options=bold>{$character->name}</>");
            $this->line("   - ID (ULID)     : <comment>{$character->id}</comment>");
            $this->line("   - Poziom (Level): <comment>{$character->level}</comment>");
            $this->line("   - Lokacja       : <comment>" . ($character->current_location ?? 'Brak') . "</comment>");
            $this->line("   - Ostatnio aktywny: <comment>" . ($character->last_active_at ? $character->last_active_at->diffForHumans() : 'Brak danych') . "</comment>");
        } else {
            $this->newLine();
            $this->warn(" Użytkownik nie posiada przypisanej żadnej postaci.");
        }

        $this->newLine();
        return Command::SUCCESS;
    }
}
