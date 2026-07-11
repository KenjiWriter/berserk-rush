<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE encounters DROP CHECK chk_state_enc");
        } catch (\Exception $e) {
            try {
                DB::statement("ALTER TABLE encounters DROP CONSTRAINT chk_state_enc");
            } catch (\Exception $e2) {
                // Ignore
            }
        }
    }

    public function down(): void
    {
        // Not recreating it because we need multiple states ('ongoing','win','loss','cancelled','error','finished')
        // and string validation is mostly handled by application logic.
    }
};
