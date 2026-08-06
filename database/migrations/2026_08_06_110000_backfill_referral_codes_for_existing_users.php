<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $existingCodes = DB::table('users')->whereNotNull('referral_code')->pluck('referral_code')->flip();

        DB::table('users')->whereNull('referral_code')->orderBy('id')->select('id')->chunkById(200, function ($users) use (&$existingCodes) {
            foreach ($users as $user) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (isset($existingCodes[$code]));

                $existingCodes[$code] = true;

                DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
            }
        });
    }

    public function down(): void
    {
        // Nieodwracalne celowo - kody referencyjne nie powinny być usuwane wstecz.
    }
};
