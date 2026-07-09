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
        Schema::create('merchant_items', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->string('item_template_id');
            $table->integer('required_level')->default(1);
            $table->boolean('is_limited')->default(false);
            $table->integer('max_quantity')->nullable();
            $table->integer('sold_quantity')->default(0);
            $table->timestamps();

            $table->foreign('item_template_id')->references('id')->on('item_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_items');
    }
};
