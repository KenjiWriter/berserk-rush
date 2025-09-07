<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->integer('quantity_change')->after('ref_id');
        });
    }

    public function down()
    {
        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->dropColumn('quantity_change');
        });
    }
};
