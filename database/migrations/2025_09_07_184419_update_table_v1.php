<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->index(['character_id', 'created_at']);
            $table->index(['character_id', 'currency_type']);
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_ledgers');
    }
};
