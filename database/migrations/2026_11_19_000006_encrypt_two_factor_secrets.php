<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->orderBy('id')
            ->get(['id', 'two_factor_secret'])
            ->each(function (object $user): void {
                try {
                    Crypt::decryptString($user->two_factor_secret);

                    return;
                } catch (DecryptException) {
                    DB::table('users')->where('id', $user->id)->update([
                        'two_factor_secret' => Crypt::encryptString($user->two_factor_secret),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Encrypted secrets must remain encrypted on rollback.
    }
};
