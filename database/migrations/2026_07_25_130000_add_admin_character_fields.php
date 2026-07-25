<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('muted_until')->nullable()->after('permission_level');
            $table->timestamp('last_active_at')->nullable()->after('muted_until');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->string('current_location')->default('Miasto (Centrum)')->after('avatar');
            $table->timestamp('last_active_at')->nullable()->after('current_location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['muted_until', 'last_active_at']);
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['current_location', 'last_active_at']);
        });
    }
};
