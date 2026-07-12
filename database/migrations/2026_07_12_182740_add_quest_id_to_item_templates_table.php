<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->foreignId('quest_id')->nullable()->constrained('quests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->dropForeign(['quest_id']);
            $table->dropColumn('quest_id');
        });
    }
};
