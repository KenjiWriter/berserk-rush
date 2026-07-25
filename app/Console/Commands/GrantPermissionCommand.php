<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Persistence\Character;

class GrantPermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:grant-admin 
                            {identifier? : ID/Email użytkownika lub ID/Nazwa postaci}
                            {--level=9 : Poziom uprawnień do nadania (domyślnie 9 - Admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nadaje uprawnienia administratora (permission_level=9) użytkownikowi po ID, e-mailu lub po ID/nazwie jego postaci.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $level = (int) $this->option('level');

        $user = null;

        if ($identifier) {
            // 1. Jeśli numeryczne - szukanie Usera po ID (bigint)
            if (is_numeric($identifier)) {
                $user = User::find((int) $identifier);
            }

            // 2. Szukanie Postaci po ULID / ID (string) i pobranie jej usera
            if (!$user) {
                $character = Character::find($identifier);
                if ($character) {
                    $user = $character->user;
                    $this->info("Znaleziono postać: {$character->name} (ULID: {$character->id}). Owner User ID: {$user->id}");
                }
            }

            // 3. Szukanie Usera po Emailu
            if (!$user) {
                $user = User::where('email', $identifier)->first();
            }

            // 4. Szukanie Postaci po Nazwie i pobranie jej usera
            if (!$user) {
                $character = Character::where('name', $identifier)->first();
                if ($character) {
                    $user = $character->user;
                    $this->info("Znaleziono postać po nazwie: {$character->name}. Owner User ID: {$user->id}");
                }
            }
        } else {
            // Brak identyfikatora - znajdźmy pierwszego usera lub wyświetlmy listę
            $user = User::latest()->first();
            if ($user) {
                $this->info("Brak podanego identyfikatora. Wybrano najnowsze konto w bazie: {$user->email} (ID: {$user->id})");
            }
        }

        if (!$user) {
            $this->error("Nie znaleziono użytkownika ani postaci dla identyfikatora: '{$identifier}'");
            return Command::FAILURE;
        }

        $oldLevel = $user->permission_level ?? 0;
        $user->permission_level = $level;
        $user->save();

        $this->newLine();
        $this->info("SUKCES: Zaktualizowano uprawnienia dla użytkownika '{$user->name}' ({$user->email})");
        $this->line(" - User ID: <comment>{$user->id}</comment>");
        $this->line(" - Stary permission_level: <comment>{$oldLevel}</comment>");
        $this->line(" - Nowy permission_level: <fg=green;options=bold>{$user->permission_level}</>");

        // Pokaż powiązane postacie konta
        $characters = $user->characters;
        if ($characters->count() > 0) {
            $this->newLine();
            $this->info("Powiązane postacie konta:");
            foreach ($characters as $char) {
                $this->line(" - Postać: <comment>{$char->name}</comment> (ULID: <comment>{$char->id}</comment>)");
            }
        }

        return Command::SUCCESS;
    }
}
