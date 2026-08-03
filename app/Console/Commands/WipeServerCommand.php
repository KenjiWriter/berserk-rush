<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:wipe 
                            {--users-only : Wipe character data and preserve only user accounts}
                            {--reset-characters : Preserve character records but reset level to 1 and stats to initial values}
                            {--force : Force execution without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe serwera: czyści i re-seeduje świat gry (migrate:fresh --seed), zachowując konta graczy (users) oraz opcjonalnie postacie.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('OSTRZEŻENIE: Ta komenda usunie wszystkie dynamiczne dane rozgrywki i ponownie zainicjuje bazę danych (migrate:fresh --seed). Czy chcesz kontynuować?')) {
            $this->warn('Operacja wipe została anulowana.');
            return Command::SUCCESS;
        }

        $usersOnly = $this->option('users-only');
        $resetCharacters = $this->option('reset-characters');

        $this->info('--- ROZPOCZĘCIE PROCEDURY WIPE SERWERA ---');

        // 1. Kopia zapasowa tabeli users
        $this->comment('1. Tworzenie kopii zapasowej tabeli `users`...');
        $users = DB::table('users')->get()->map(function ($row) {
            $data = (array) $row;
            foreach ($data as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $data[$k] = json_encode($v);
                }
            }
            return $data;
        })->toArray();

        $this->info('-> Zapisano kont użytkowników: ' . count($users));

        // 2. Kopia zapasowa tabeli characters (jeśli nie wybrano --users-only)
        $characters = [];
        if (!$usersOnly && Schema::hasTable('characters')) {
            $this->comment('2. Tworzenie kopii zapasowej tabeli `characters`...');
            $characters = DB::table('characters')->get()->map(function ($row) use ($resetCharacters) {
                $data = (array) $row;

                // Odpinamy usunięte relacje (gildia)
                $data['guild_id'] = null;

                if ($resetCharacters) {
                    $data['level'] = 1;
                    $data['xp'] = 0;
                    $data['gold'] = 0;
                    $data['character_points'] = 10;
                    $data['skill_points'] = 0;
                    $data['elo'] = 1000;
                    $data['arena_tokens'] = 0;
                    $data['achievement_points'] = 0;
                }

                foreach ($data as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        $data[$k] = json_encode($v);
                    }
                }
                return $data;
            })->toArray();

            $this->info('-> Zapisano postaci: ' . count($characters));
        }

        // 3. Wykonanie migrate:fresh --seed
        $this->comment('3. Wykonywanie migracjonalizacji i seedowania bazy (`php artisan migrate:fresh --seed`)...');
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // 4. Usuwanie użytkownika testowego z DatabaseSeeder (jeśli istnieje, aby uniknąć kolizji email)
        DB::table('users')->where('email', 'test@example.com')->delete();

        // 5. Przywracanie tabeli users
        if (!empty($users)) {
            $this->comment('4. Przywracanie kont użytkowników...');
            foreach (array_chunk($users, 100) as $chunk) {
                DB::table('users')->insert($chunk);
            }
            $this->info('-> Przywrócono ' . count($users) . ' użytkowników.');

            // Naprawa sekwencji id w PostgreSQL
            if (DB::getDriverName() === 'pgsql') {
                $maxUserId = DB::table('users')->max('id') ?? 0;
                if ($maxUserId > 0) {
                    DB::statement("SELECT setval('users_id_seq', {$maxUserId})");
                    $this->info("-> Zaktualizowano sekwencję users_id_seq na {$maxUserId}.");
                }
            }
        }

        // 6. Przywracanie tabeli characters
        if (!$usersOnly && !empty($characters)) {
            $this->comment('5. Przywracanie postaci...');

            // Weryfikacja active_title_id z zarejestrowanymi tytułami
            $validTitleIds = DB::table('titles')->pluck('id')->toArray();
            foreach ($characters as &$char) {
                if (isset($char['active_title_id']) && !in_array($char['active_title_id'], $validTitleIds, true)) {
                    $char['active_title_id'] = null;
                }
            }
            unset($char);

            foreach (array_chunk($characters, 100) as $chunk) {
                DB::table('characters')->insert($chunk);
            }
            $this->info('-> Przywrócono ' . count($characters) . ' postaci.');
        }

        $this->info('=== WIPE SERWERA ZAKOŃCZONY POMYŚLNIE ===');

        return Command::SUCCESS;
    }
}
