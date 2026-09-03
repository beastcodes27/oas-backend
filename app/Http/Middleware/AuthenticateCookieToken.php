<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lift the httpOnly authentication cookie back into an Authorization header
 * so Sanctum can authenticate the request without JavaScript ever seeing the
 * token value. Explicit Authorization headers (external API clients) always
 * take precedence over the cookie.
 */
class AuthenticateCookieToken
{
    public const COOKIE_NAME = 'oas_token';

    public function handle(Request $request, Closure $next): Response
    {
        $cookieToken = $request->cookie(self::COOKIE_NAME);

        if ($cookieToken !== null && ! $request->headers->has('authorization')) {
            $request->attributes->set('oas_cookie_auth', true);
            $request->headers->set('Authorization', 'Bearer '.$cookieToken);
        }

        return $next($request);
    }
}
