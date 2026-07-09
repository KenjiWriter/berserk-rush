<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('market_listings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('seller_character_id');
            $table->ulid('item_instance_id')->nullable();
            $table->ulid('material_ref_ulid')->nullable();
            $table->unsignedBigInteger('quantity')->default(1);
            $table->bigInteger('price')->default(0);
            $table->string('currency', 8)->default('gold'); // gold|gems
            $table->string('status', 12)->default('active'); // active|sold|expired|cancelled
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('seller_character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('item_instance_id')->references('id')->on('item_instances')->nullOnDelete();

            $table->index(['status', 'created_at']);
            $table->index(['seller_character_id', 'status']);
        });

        try {
            DB::statement("ALTER TABLE market_listings ADD CONSTRAINT chk_currency_ml CHECK (currency IN ('gold','gems'))");
            DB::statement("ALTER TABLE market_listings ADD CONSTRAINT chk_status_ml CHECK (status IN ('active','sold','expired','cancelled'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        Schema::create('purchases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('listing_id');
            $table->ulid('buyer_character_id');
            $table->bigInteger('price_paid');
            $table->string('currency', 8);
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('market_listings')->cascadeOnDelete();
            $table->foreign('buyer_character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['buyer_character_id', 'created_at']);
        });

        Schema::create('currency_ledgers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('idempotency_key')->unique();
            $table->ulid('character_id');
            $table->string('currency_type')->default('gold');
            $table->bigInteger('amount');
            $table->bigInteger('balance_after');
            $table->string('source_type');
            $table->string('source_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['character_id', 'created_at']);
            $table->index(['character_id', 'currency_type']);
            $table->index('source_type');
        });

        try {
            DB::statement("ALTER TABLE currency_ledgers ADD CONSTRAINT chk_currency_cl CHECK (currency_type IN ('gold','gems'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        Schema::create('mail', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('to_character_id');
            $table->string('subject')->default('');
            $table->text('body')->nullable();
            $table->json('attachments')->nullable(); // np. [{type:item, id:ULID}, {type:gold, qty:100}]
            $table->boolean('claimed')->default(false);
            $table->timestamps();

            $table->foreign('to_character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['to_character_id', 'claimed']);
        });

        // PARTIAL INDEX (Postgres): tylko aktywne oferty
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX IF NOT EXISTS market_listings_active_idx ON market_listings (created_at) WHERE status = 'active';");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS market_listings_active_idx;");
        }

        try {
            DB::statement("ALTER TABLE currency_ledgers DROP CONSTRAINT IF EXISTS chk_currency_cl");
            DB::statement("ALTER TABLE market_listings DROP CONSTRAINT IF EXISTS chk_status_ml");
            DB::statement("ALTER TABLE market_listings DROP CONSTRAINT IF EXISTS chk_currency_ml");
        } catch (\Exception $e) {
            // Ignore
        }

        Schema::dropIfExists('mail');
        Schema::dropIfExists('currency_ledgers');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('market_listings');
    }
};
