<?php

namespace App\Services;

use App\Models\IntegrationLog;
use Carbon\CarbonInterface;

/**
 * Aggregates integration_logs into the numbers the dashboard shows.
 *
 * A service rather than query code in the controller, because these figures
 * will also feed alerting and the per-family health view
 * (docs/11-logging-monitoring.md).
 */
class IntegrationMetrics
{
    public const DEFAULT_WINDOW_HOURS = 24;

    /**
     * Headline counts for the window.
     *
     * @return array{
     *     windowHours: int,
     *     total: int,
     *     successful: int,
     *     failed: int,
     *     successRate: float|null,
     *     averageDurationMs: int|null
     * }
     */
    public function summary(int $windowHours = self::DEFAULT_WINDOW_HOURS): array
    {
        $since = $this->since($windowHours);

        $total = IntegrationLog::query()->where('created_at', '>=', $since)->count();

        $successful = IntegrationLog::query()
            ->where('created_at', '>=', $since)
            ->where('outcome', IntegrationLog::OUTCOME_SUCCESS)
            ->count();

        $averageDuration = IntegrationLog::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');

        return [
            'windowHours' => $windowHours,
            'total' => $total,
            'successful' => $successful,
            'failed' => $total - $successful,
            // Null rather than 0 or 100 when nothing happened. Either number
            // would be a claim the data does not support, and a red 0% on a
            // quiet morning would send someone chasing an outage that is not
            // happening.
            'successRate' => $total === 0 ? null : round($successful / $total * 100, 1),
            'averageDurationMs' => is_numeric($averageDuration) ? (int) round((float) $averageDuration) : null,
        ];
    }

    /**
     * Call counts keyed by outcome.
     *
     * @return array<string, int>
     */
    public function byOutcome(int $windowHours = self::DEFAULT_WINDOW_HOURS): array
    {
        /** @var array<string, int> $counts */
        $counts = IntegrationLog::query()
            ->where('created_at', '>=', $this->since($windowHours))
            ->selectRaw('outcome, count(*) as aggregate')
            ->groupBy('outcome')
            ->pluck('aggregate', 'outcome')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        return $counts;
    }

    /**
     * Totals and failures per base URL family.
     *
     * Availability is per family, not global: the families are different hosts
     * and can fail independently (ADR-010).
     *
     * Each family also says how its most recent call in the window went and
     * when: the connection status the dashboard shows. A family whose last
     * call failed is the one to look at, even when most calls succeeded.
     *
     * @return array<string, array{total: int, failed: int, successRate: float|null, lastOutcome: string|null, lastCallAt: string|null}>
     */
    public function byFamily(int $windowHours = self::DEFAULT_WINDOW_HOURS): array
    {
        $since = $this->since($windowHours);

        $rows = IntegrationLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('family, outcome, count(*) as aggregate')
            ->groupBy('family', 'outcome')
            ->get();

        $families = [];

        foreach ($rows as $row) {
            /** @var string $family */
            $family = $row->getAttribute('family');
            $count = (int) $row->getAttribute('aggregate');

            $families[$family] ??= ['total' => 0, 'failed' => 0, 'successRate' => null, 'lastOutcome' => null, 'lastCallAt' => null];
            $families[$family]['total'] += $count;

            if ($row->getAttribute('outcome') !== IntegrationLog::OUTCOME_SUCCESS) {
                $families[$family]['failed'] += $count;
            }
        }

        foreach ($families as $family => $counts) {
            $families[$family]['successRate'] = $counts['total'] === 0
                ? null
                : round(($counts['total'] - $counts['failed']) / $counts['total'] * 100, 1);

            // One query per family: there are a handful of families, and the
            // log table is too large to load the window and pick in PHP.
            $last = IntegrationLog::query()
                ->where('created_at', '>=', $since)
                ->where('family', $family)
                ->latest('created_at')
                ->latest('id')
                ->first(['outcome', 'created_at']);

            $families[$family]['lastOutcome'] = $last?->outcome;
            $families[$family]['lastCallAt'] = $last?->created_at?->toIso8601String();
        }

        return $families;
    }

    /**
     * The most recent failures, for the "what is broken right now" panel.
     *
     * @return list<array<string, mixed>>
     */
    public function recentFailures(int $limit = 10, int $windowHours = self::DEFAULT_WINDOW_HOURS): array
    {
        $logs = IntegrationLog::query()
            ->where('created_at', '>=', $this->since($windowHours))
            ->where('outcome', '!=', IntegrationLog::OUTCOME_SUCCESS)
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();

        $failures = [];

        foreach ($logs as $log) {
            $failures[] = [
                'id' => $log->id,
                'correlationId' => $log->correlation_id,
                'operation' => $log->operation,
                'family' => $log->family,
                'outcome' => $log->outcome,
                'esbCode' => $log->esb_code,
                // Already the safe summary -- the upstream message never
                // reaches this table.
                'message' => $log->safe_error_message,
                'createdAt' => $log->created_at?->toIso8601String(),
            ];
        }

        return $failures;
    }

    /**
     * AppServiceProvider pins Date::use(CarbonImmutable::class), so now()
     * returns an immutable instance -- hence the interface, not the concrete
     * Illuminate\Support\Carbon.
     */
    private function since(int $windowHours): CarbonInterface
    {
        return now()->subHours($windowHours);
    }
}
