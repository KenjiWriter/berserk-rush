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
        Schema::create('item_shop_packages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->integer('gem_amount');
            $table->integer('price_in_cents');
            $table->string('currency')->default('PLN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_shop_packages');
    }
};
