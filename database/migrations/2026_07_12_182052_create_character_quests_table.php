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
        Schema::create('character_quests', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, completed, rewarded, cancelled
            $table->integer('progress')->default(0);
            $table->timestamps();
            
            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->unique(['character_id', 'quest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_quests');
    }
};
