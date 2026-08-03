<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ResetAllGemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-gems {--force : Wykonanie bez potwierdzenia}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zeruje balans gemów (waluty premium) dla wszystkich kont użytkowników w bazie danych.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('OSTRZEŻENIE: Czy na pewno chcesz wyzerować gemy wszystkim użytkownikom?')) {
            $this->warn('Operacja została anulowana.');
            return Command::SUCCESS;
        }

        $count = User::where('gems', '>', 0)->count();

        DB::transaction(function () {
            User::query()->update(['gems' => 0]);
        });

        $this->info("Pomyślnie wyzerowano gemy wszystkim użytkownikom (zaktualizowano {$count} kont z dodatnim bilansem gemów).");

        return Command::SUCCESS;
    }
}
