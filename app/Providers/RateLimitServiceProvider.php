<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateApiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerEsbLimiter();
        $this->registerSalesPushApiLimiter();
    }

    /**
     * Budget for POS clients calling /api/v1/sales-data (ADR-022).
     *
     * Keyed per API client, so one misbehaving POS cannot starve the others.
     * The api-client middleware runs first, so the client is always known.
     */
    protected function registerSalesPushApiLimiter(): void
    {
        RateLimiter::for('sales-push-api', function (Request $request): Limit {
            $perMinute = (int) config('sales_push.rate_limit_per_minute', 120);
            $client = $request->attributes->get(AuthenticateApiClient::ATTRIBUTE);

            return Limit::perMinute(max(1, $perMinute))
                ->by('api-client:'.($client?->getKey() ?? $request->ip()))
                ->response(fn (): Response => response()->json([
                    'message' => 'Too many requests from this client. Wait a minute and try again.',
                ], 429));
        });
    }

    /**
     * Budget for routes that reach the ESB.
     *
     * Keyed per user rather than globally: one runaway script must not lock out
     * the whole team. Falls back to the client IP for the (currently
     * non-existent) unauthenticated case, so the limiter can never key on null
     * and silently become global.
     */
    protected function registerEsbLimiter(): void
    {
        RateLimiter::for('esb', function (Request $request): Limit {
            $perMinute = (int) config('esb.rate_limit.per_minute', 60);

            return Limit::perMinute(max(1, $perMinute))
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))
                ->response(fn (): Response => response()->json([
                    'message' => 'Too many ERP requests from this account. Wait a minute and try again.',
                ], 429));
        });
    }
}
