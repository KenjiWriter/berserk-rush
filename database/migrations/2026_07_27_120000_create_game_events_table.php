<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('multiplier', 5, 2)->default(2.00);
            $table->boolean('is_active')->default(false);
            $table->string('mode')->default('auto'); // 'auto' or 'manual'
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
