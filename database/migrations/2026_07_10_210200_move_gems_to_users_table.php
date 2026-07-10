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
            $table->bigInteger('gems')->default(0)->after('password');
        });

        // Optional: migrate existing gems from characters to users
        $characters = \Illuminate\Support\Facades\DB::table('characters')->select('user_id', \Illuminate\Support\Facades\DB::raw('SUM(gems) as total_gems'))->groupBy('user_id')->get();
        foreach ($characters as $char) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $char->user_id)->update(['gems' => $char->total_gems]);
        }

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('gems');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->bigInteger('gems')->default(0)->after('gold');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gems');
        });
    }
};
