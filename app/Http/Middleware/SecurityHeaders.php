<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds browser security headers to every web response: no MIME sniffing,
 * no framing by other sites, a restrictive referrer and feature policy, and
 * HSTS once the site is served over HTTPS.
 */
class SecurityHeaders
{
    /**
     * @var array<string, string>
     */
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        // Script sources are left open because Inertia and Vite inject inline
        // bootstrapping; these directives still block plugins, <base>
        // hijacking, off-site form posts and framing.
        'Content-Security-Policy' => "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $header => $value) {
            if (! $response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
