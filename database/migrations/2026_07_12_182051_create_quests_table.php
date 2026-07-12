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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // gathering, hunting, action
            $table->integer('required_level')->default(1);
            $table->integer('max_level')->nullable();
            
            $table->string('target_type')->nullable(); 
            $table->string('target_id')->nullable();
            $table->integer('target_amount')->default(1);
            
            $table->integer('reward_gold')->default(0);
            $table->integer('reward_exp')->default(0);
            $table->json('reward_items')->nullable(); // np. [{"template_id": "ulid", "amount": 1}]
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
