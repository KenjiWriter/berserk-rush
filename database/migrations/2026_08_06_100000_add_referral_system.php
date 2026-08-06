<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('deletion_code');
            $table->string('referred_by_user_id')->nullable()->index()->after('referral_code');
            $table->timestamp('referral_signup_bonus_claimed_at')->nullable()->after('referred_by_user_id');
            $table->timestamp('referral_mirror_bonus_until')->nullable()->after('referral_signup_bonus_claimed_at');
            $table->timestamp('referral_level30_reward_granted_at')->nullable()->after('referral_mirror_bonus_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'referral_code',
                'referred_by_user_id',
                'referral_signup_bonus_claimed_at',
                'referral_mirror_bonus_until',
                'referral_level30_reward_granted_at',
            ]);
        });
    }
};
