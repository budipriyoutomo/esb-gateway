<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\IntegrationMetrics;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function index(IntegrationMetrics $metrics): Response
    {
        return Inertia::render('dashboard', [
            'metrics' => $metrics->summary(),
            'byOutcome' => $metrics->byOutcome(),
            'byFamily' => $metrics->byFamily(),
            'recentFailures' => $metrics->recentFailures(limit: 8),
            'esb' => [
                'environment' => config('esb.environment'),
                // The mechanism is useful for an operator to see. The key
                // itself never leaves the server (docs/10-security.md).
                'authMechanism' => config('esb.auth.mechanism'),
            ],
            'queue' => [
                'pending' => $this->pendingJobs(),
            ],
        ]);
    }

    /**
     * Depth of the default queue, or null when it cannot be read.
     *
     * Redis may be unreachable, and a dashboard that 500s because the queue
     * backend is down is worse than one that says it does not know.
     */
    private function pendingJobs(): ?int
    {
        try {
            return Queue::size();
        } catch (Throwable) {
            return null;
        }
    }
}
