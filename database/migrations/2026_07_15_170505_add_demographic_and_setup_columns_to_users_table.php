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
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            $table->string('age_range')->nullable();
            $table->string('location')->nullable();
            $table->string('hometown')->nullable();
            $table->string('profile_url')->nullable();
            $table->boolean('is_social_setup_pending')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'birthday',
                'age_range',
                'location',
                'hometown',
                'profile_url',
                'is_social_setup_pending'
            ]);
        });
    }
};
