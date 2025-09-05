<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Poziomy upgrade 0..9
        DB::statement("ALTER TABLE item_instances ADD CONSTRAINT chk_upgrade_level CHECK (upgrade_level BETWEEN 0 AND 9)");
        // Waluty dozwolone
        DB::statement("ALTER TABLE currency_ledger ADD CONSTRAINT chk_currency CHECK (currency IN ('gold','gems'))");
        DB::statement("ALTER TABLE market_listings ADD CONSTRAINT chk_currency_ml CHECK (currency IN ('gold','gems'))");
        // Stany encountera
        DB::statement("ALTER TABLE encounters ADD CONSTRAINT chk_state_enc CHECK (state IN ('ongoing','win','lose'))");
        // Statusy aukcji
        DB::statement("ALTER TABLE market_listings ADD CONSTRAINT chk_status_ml CHECK (status IN ('active','sold','expired','cancelled'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE item_instances DROP CONSTRAINT IF EXISTS chk_upgrade_level");
        DB::statement("ALTER TABLE currency_ledger DROP CONSTRAINT IF EXISTS chk_currency");
        DB::statement("ALTER TABLE market_listings DROP CONSTRAINT IF EXISTS chk_currency_ml");
        DB::statement("ALTER TABLE encounters DROP CONSTRAINT IF EXISTS chk_state_enc");
        DB::statement("ALTER TABLE market_listings DROP CONSTRAINT IF EXISTS chk_status_ml");
    }
};
