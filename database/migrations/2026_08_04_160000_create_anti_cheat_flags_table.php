<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anti_cheat_flags', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->string('type', 30); // np. 'kill_rate'
            $table->string('severity', 10); // 'medium' | 'high'
            $table->unsignedInteger('metric_value');
            $table->unsignedInteger('threshold');
            $table->unsignedSmallInteger('window_minutes');
            $table->json('details')->nullable();
            $table->string('status', 12)->default('open'); // open|reviewed|dismissed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->index(['character_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anti_cheat_flags');
    }
};
