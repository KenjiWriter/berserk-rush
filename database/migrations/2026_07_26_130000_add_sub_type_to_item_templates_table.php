<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->string('sub_type')->nullable()->after('type');
            $table->index('sub_type');
        });
    }

    public function down(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->dropIndex(['sub_type']);
            $table->dropColumn('sub_type');
        });
    }
};
