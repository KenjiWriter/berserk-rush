<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usuń całą tabelę i utwórz od nowa z prawidłową strukturą
        Schema::dropIfExists('item_ledgers');

        Schema::create('item_ledgers', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('character_id', 26);
            $table->string('item_instance_id', 26);
            $table->string('action'); // drop, pickup, trade, etc.
            $table->string('ref_type'); // encounter, trade, manual, etc.
            $table->string('ref_id')->nullable(); // ID referencji
            $table->integer('quantity_change')->default(1);
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            // Indeksy
            $table->index(['character_id', 'created_at']);
            $table->index(['action', 'ref_type']);
            $table->index('idempotency_key');

            // Klucze obce (jeśli tabele docelowe istnieją)
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('item_instance_id')->references('id')->on('item_instances')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_ledgers');
    }
};
