<?php

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

/**
 * Unauthenticated health check for load balancers and deployment verification
 * (docs/13-deployment.md).
 *
 * The body is public, so it says what is broken and never where or why: a
 * database error message can carry a host, a port, and a username. The detail
 * goes to the application log instead, where it is already protected.
 *
 * The ESB is deliberately not probed here. A load balancer polling every few
 * seconds would hammer it, and a third party being down would pull our own
 * instances out of rotation. ESB reachability lives on the connections screen,
 * where a person asks for it.
 */
class HealthController extends Controller
{
    private const OK = 'ok';

    private const FAILED = 'failed';

    public function __invoke(): JsonResponse
    {
        $checks = [
            'application' => self::OK,
            'database' => $this->check('database', fn () => DB::connection()->getPdo()),
            'cache' => $this->check('cache', function (): void {
                $probe = 'health:'.Str::random(8);

                Cache::put($probe, true, 5);
                Cache::forget($probe);
            }),
            'queue' => $this->check('queue', fn () => Queue::size()),
        ];

        $healthy = ! in_array(self::FAILED, $checks, true);

        return response()->json([
            'status' => $healthy ? self::OK : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Run one dependency check, swallowing the reason.
     */
    private function check(string $name, callable $probe): string
    {
        try {
            $probe();

            return self::OK;
        } catch (Throwable $exception) {
            Log::error('Health check failed', [
                'check' => $name,
                'reason' => $exception->getMessage(),
            ]);

            return self::FAILED;
        }
    }
}
