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
        Schema::create('item_recipes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('result_item_template_id');
            $table->json('ingredients');
            $table->integer('gold_cost')->default(0);
            $table->timestamps();

            $table->foreign('result_item_template_id')->references('id')->on('item_templates')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_recipes');
    }
};
