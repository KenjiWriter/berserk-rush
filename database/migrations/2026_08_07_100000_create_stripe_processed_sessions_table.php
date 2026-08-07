<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Security fix (2026-08-07): closes a double-credit race between the Stripe webhook and the
// itemshop success-url redirect handler, both of which previously used a non-atomic
// Cache::has()/Cache::put() check to decide whether a checkout session's gems were already
// granted. A DB-level unique constraint, inserted inside the same transaction as the gem
// credit, makes "has this session already been processed" an atomic decision.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('stripe_processed_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_session_id')->unique();
            $table->ulid('user_id');
            $table->unsignedInteger('gem_amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_processed_sessions');
    }
};
