<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->ulid('active_title_id')->nullable();
            $table->unsignedInteger('achievement_points')->default(0);

            $table->foreign('active_title_id')->references('id')->on('titles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign(['active_title_id']);
            $table->dropColumn('active_title_id');
            $table->dropColumn('achievement_points');
        });
    }
};
