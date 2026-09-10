<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers (docs/10-security.md).
 */
class SecurityHeaders
{
    /**
     * Two years, the minimum for HSTS preload eligibility.
     */
    private const HSTS_MAX_AGE = 63072000;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Naming the framework and version tells an attacker which CVEs to try
        // first.
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // HSTS only over https in production. Over plain http it is
        // meaningless, and sent from a local dev server it would pin the
        // developer's browser to https for localhost -- which then breaks every
        // other local project on that host.
        if ($request->secure() && app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.self::HSTS_MAX_AGE.'; includeSubDomains',
            );
        }

        return $response;
    }
}
