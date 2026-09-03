<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defend cookie-authenticated requests against cross-site request forgery.
 *
 * The authentication cookie is SameSite=None in production (the SPA and API
 * live on different subdomains), so it is sent automatically on any request.
 * Requests that authenticate through that cookie must carry an X-OAS-Request
 * header — a cross-site attacker cannot set this header because the browser
 * blocks the CORS pre-flight for origins not on the allow-list, and HTML
 * forms cannot send custom headers. External clients that authenticate with
 * an explicit Authorization header are unaffected.
 */
class VerifyCookieRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()
            && $request->attributes->get('oas_cookie_auth', false)
            && ! $request->headers->has('x-oas-request')) {
            abort(419, 'Session expired. Please refresh and sign in again.');
        }

        return $next($request);
    }
}
