<?php

namespace App\Console\Commands;

use App\Models\IntegrationLog;
use Illuminate\Console\Command;

/**
 * Prunes integration_logs beyond the retention window
 * (docs/11-logging-monitoring.md).
 *
 * Audit logs are deliberately out of scope: they record who changed what, are
 * immutable by design (ADR-012), and their retention is a compliance question
 * rather than a volume one. Pruning them needs its own explicit decision.
 */
class PruneIntegrationLogsCommand extends Command
{
    protected $signature = 'integration-logs:prune
        {--dry-run : Report what would be removed without deleting anything}';

    protected $description = 'Delete integration log entries beyond the retention window';

    /**
     * Deleted in batches so a large backlog does not hold one long transaction
     * or a lock table full of rows nobody is reading.
     */
    private const CHUNK = 1000;

    public function handle(): int
    {
        $days = (int) config('esb.retention.integration_logs_days', 0);

        if ($days <= 0) {
            $this->info('Retention is disabled (esb.retention.integration_logs_days is 0). Nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $query = IntegrationLog::query()->where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info(sprintf('No integration logs older than %d days. Nothing pruned.', $days));

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                'Dry run: %d integration log entries older than %d days would be removed.',
                $count,
                $days,
            ));

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $batch = IntegrationLog::query()
                ->where('created_at', '<', $cutoff)
                ->limit(self::CHUNK)
                ->delete();

            $deleted += $batch;
        } while ($batch > 0);

        $this->info(sprintf(
            'Removed %d integration log entries older than %d days.',
            $deleted,
            $days,
        ));

        return self::SUCCESS;
    }
}
