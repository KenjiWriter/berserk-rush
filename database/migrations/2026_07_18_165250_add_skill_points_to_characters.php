<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('skill_points')->default(0);
        });

        // Wsteczne przypisanie punktów skilli postaciom (1 punkt za poziom, zaczynając od poziomu 2 - ewentualnie od 1. Przyjmijmy level - 1 punktów)
        DB::statement('UPDATE characters SET skill_points = CASE WHEN (level - 1) > 0 THEN (level - 1) ELSE 0 END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('skill_points');
        });
    }
};
