<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Signs a user out everywhere: deletes every Sanctum token and, with the database session
 * driver, every web session row. Remember-me cookies stop working because the caller rotates
 * the remember token.
 */
class SessionRevoker
{
    /**
     * @return array{tokens_revoked:int, sessions_revoked:int}
     */
    public static function revokeAll(User $user): array
    {
        $tokens = $user->tokens()->count();
        $user->tokens()->delete();

        // Clear stored web sessions whenever the sessions table exists (the production driver
        // is "database"; with other drivers the rotated remember token still ends remember-me).
        $sessions = 0;
        $table = config('session.table', 'sessions');
        if (Schema::hasTable($table)) {
            $sessions = DB::table($table)->where('user_id', $user->id)->delete();
        }

        return ['tokens_revoked' => $tokens, 'sessions_revoked' => $sessions];
    }
}
