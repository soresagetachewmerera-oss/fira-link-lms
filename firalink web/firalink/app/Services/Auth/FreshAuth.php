<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * "Fresh authentication" for sensitive actions: the user proved who they are (a sign-in code,
 * a step-up code, or their password) within the last WINDOW_MINUTES.
 *
 * - API: tracked per Sanctum token, so a stolen older token cannot do sensitive actions.
 * - Web: tracked in the session.
 */
class FreshAuth
{
    public const WINDOW_MINUTES = 10;

    private const SESSION_KEY = 'fresh_auth_at';

    private static function tokenKey(int $tokenId): string
    {
        return 'fresh_auth_token_' . $tokenId;
    }

    /** Mark a newly issued API token as fresh (the user has just signed in). */
    public static function markToken(NewAccessToken|PersonalAccessToken|int $token): void
    {
        $id = match (true) {
            $token instanceof NewAccessToken => $token->accessToken->id,
            $token instanceof PersonalAccessToken => $token->id,
            default => $token,
        };

        Cache::put(self::tokenKey((int) $id), now()->toIso8601String(), now()->addMinutes(self::WINDOW_MINUTES));
    }

    private static function userKey(int $userId): string
    {
        return 'fresh_auth_user_' . $userId;
    }

    /**
     * Mark a user as fresh when the request has neither a personal access token nor a session
     * (for example Sanctum's transient tokens in tests).
     */
    public static function markUser(User $user): void
    {
        Cache::put(self::userKey($user->id), now()->toIso8601String(), now()->addMinutes(self::WINDOW_MINUTES));
    }

    /** A stored personal access token (not a transient or test token without an id). */
    private static function realTokenId($token): ?int
    {
        if (!$token instanceof PersonalAccessToken) {
            return null;
        }
        $id = $token->getKey();

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    /** Mark the current request's token or session as fresh. */
    public static function markRequest(Request $request): void
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($id = self::realTokenId($token)) {
            self::markToken($id);
        } elseif ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, now()->timestamp);
        } elseif ($user) {
            self::markUser($user);
        }
    }

    public static function isFresh(Request $request): bool
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($id = self::realTokenId($token)) {
            return Cache::has(self::tokenKey($id));
        }

        if ($request->hasSession()) {
            $at = (int) $request->session()->get(self::SESSION_KEY, 0);

            return $at > 0 && now()->timestamp - $at <= self::WINDOW_MINUTES * 60;
        }

        return $user !== null && Cache::has(self::userKey($user->id));
    }

    /** Mark a web session as fresh (after a password or code sign-in). */
    public static function markSession(\Illuminate\Contracts\Session\Session $session): void
    {
        $session->put(self::SESSION_KEY, now()->timestamp);
    }

    /**
     * The request carries the user's current password (e.g. a password-change form).
     * Checking it counts as fresh authentication for this request only.
     */
    public static function passwordInRequest(Request $request, User $user): bool
    {
        $password = (string) $request->input('current_password', '');

        return $password !== '' && Hash::check($password, $user->password);
    }
}
