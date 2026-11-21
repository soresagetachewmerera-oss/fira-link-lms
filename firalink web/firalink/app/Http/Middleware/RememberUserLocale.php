<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saves the language a signed-in person uses (messaging slice 1), so emails, SMS and push reach them
 * in that language. The app sends Accept-Language (en or am) on every API call; only a change is
 * written, after the response is built.
 */
class RememberUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $user = $request->user();
            $lang = strtolower(substr((string) $request->header('Accept-Language', ''), 0, 2));
            if ($user && in_array($lang, ['en', 'am'], true) && $user->locale !== $lang) {
                $user->forceFill(['locale' => $lang])->saveQuietly();
            }
        } catch (\Throwable $e) {
            report($e); // never changes the answer
        }

        return $response;
    }
}
