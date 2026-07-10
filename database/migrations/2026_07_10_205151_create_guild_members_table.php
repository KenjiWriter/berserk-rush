<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guild_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('guild_id')->index();
            $table->ulid('character_id')->index();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_members');
    }
};
