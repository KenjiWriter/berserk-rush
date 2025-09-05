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
            // albo wystawiamy egzemplarz itemu:
            $table->ulid('item_instance_id')->nullable();
            // albo materiał/stack po ref_ulid + qty:
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

        Schema::create('currency_ledger', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->string('currency', 8); // gold|gems
            $table->bigInteger('delta');   // dodatnie/ujemne
            $table->bigInteger('balance_after');
            $table->string('reason', 24); // drop|sell|buy|upgrade|craft|quest|reward
            $table->string('ref_type', 24)->nullable();
            $table->ulid('ref_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['character_id', 'created_at']);
        });

        Schema::create('mail', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('to_character_id');
            $table->string('subject')->default('');
            $table->text('body')->default('');
            $table->jsonb('attachments')->default(DB::raw("'[]'::jsonb")); // np. [{type:item, id:ULID}, {type:gold, qty:100}]
            $table->boolean('claimed')->default(false);
            $table->timestamps();

            $table->foreign('to_character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['to_character_id', 'claimed']);
        });

        // PARTIAL INDEX (Postgres): tylko aktywne oferty
        DB::statement("CREATE INDEX IF NOT EXISTS market_listings_active_idx ON market_listings (created_at) WHERE status = 'active';");
    }

    public function down(): void
    {
        // usuń partial index ręcznie
        DB::statement("DROP INDEX IF EXISTS market_listings_active_idx;");

        Schema::dropIfExists('mail');
        Schema::dropIfExists('currency_ledger');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('market_listings');
    }
};
