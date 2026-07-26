<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE encounters DROP CONSTRAINT IF EXISTS chk_state_enc;");
        } elseif ($driver === 'mysql') {
            try {
                DB::statement("ALTER TABLE encounters DROP CHECK chk_state_enc;");
            } catch (\Throwable $e) {
                try {
                    DB::statement("ALTER TABLE encounters DROP CONSTRAINT chk_state_enc;");
                } catch (\Throwable $e2) {
                    // Ignore if missing
                }
            }
        }
    }

    public function down(): void
    {
    }
};
