<?php

namespace App\Jobs;

use App\Actions\SalesPushes\SendSalesPush;
use App\Models\SalesPush;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Push one stored sales transaction to the ESB (ADR-022).
 *
 * Carries ids only. The payload stays encrypted in sales_pushes and never
 * enters the queue store.
 */
class PushSalesData implements ShouldQueue
{
    use Queueable;

    /**
     * Attempts, including the first. Bounded: an ESB that stays down ends as
     * a failed push a person can retry, not an endless loop.
     */
    public int $tries;

    public function __construct(
        public readonly int $salesPushId,
        public readonly string $salesNum,
    ) {
        $this->tries = max(1, (int) config('sales_push.tries', 5));
        $this->onQueue((string) config('sales_push.queue', 'default'));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return array_values(array_map('intval', (array) config('sales_push.backoff', [30])));
    }

    /**
     * Two pushes of one transaction never run side by side, so the one the
     * ESB receives last is the one the POS sent last.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('sales-push:'.$this->salesNum))
                ->releaseAfter(15)
                ->expireAfter(180),
        ];
    }

    public function handle(SendSalesPush $send): void
    {
        $push = SalesPush::query()->find($this->salesPushId);

        if ($push === null) {
            return;
        }

        $send->handle($push);
    }

    public function failed(?Throwable $exception): void
    {
        SalesPush::query()
            ->whereKey($this->salesPushId)
            ->where('status', SalesPush::STATUS_QUEUED)
            ->update(['status' => SalesPush::STATUS_FAILED]);
    }
}
