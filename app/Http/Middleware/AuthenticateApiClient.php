<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate a system calling /api with its bearer token (ADR-022).
 *
 * A web session is never enough: the /api routes are for machines, and a
 * logged-in person's cookie must not become a way to write sales data.
 */
class AuthenticateApiClient
{
    public const ATTRIBUTE = 'apiClient';

    /**
     * @param  string|null  $scope  What the route needs (ApiClient::SCOPES), e.g. `api-client:closing-totals`.
     */
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $client = ApiClient::findActiveByToken((string) $request->bearerToken());

        if ($client === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($scope !== null && ! $client->allows($scope)) {
            return response()->json(['message' => 'This API client may not call this endpoint.'], 403);
        }

        $client->markUsed();
        $request->attributes->set(self::ATTRIBUTE, $client);

        return $next($request);
    }

    public static function client(Request $request): ApiClient
    {
        $client = $request->attributes->get(self::ATTRIBUTE);

        if (! $client instanceof ApiClient) {
            throw new \LogicException('The api-client middleware did not run for this route.');
        }

        return $client;
    }
}
