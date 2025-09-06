<?php
// filepath: database/migrations/2025_09_05_154500_fix_encounters_result_nullable.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            // Zmień kolumnę result na nullable
            $table->enum('result', ['win', 'loss'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            // Przywróć NOT NULL constraint
            $table->enum('result', ['win', 'loss'])->nullable(false)->change();
        });
    }
};
