<?php

namespace App\Actions\ClosingRecaps;

use App\Audit\AuditAction;
use App\Jobs\PushSalesData;
use App\Models\AuditLog;
use App\Models\ClosingRecap;
use App\Models\ClosingSalesPush;
use App\Models\SalesPush;
use Illuminate\Support\Facades\DB;

/**
 * Hand a closing recap's prepared Push Sales transaction to the sales push
 * module (ADR-022, ADR-032 P29). The ESB is called from the queue, which
 * repeats an unknown outcome (the ESB overwrites by salesNum) and supersedes
 * an older push of the same salesNum.
 */
class SendClosingSalesPush
{
    /**
     * @throws ClosingRecapNotReady
     */
    public function handle(ClosingRecap $recap): SalesPush
    {
        return DB::transaction(function () use ($recap): SalesPush {
            $draft = $recap->salesPushes()
                ->where('recap_sequence', $recap->sequence)
                ->where('status', ClosingSalesPush::STATUS_PREPARED)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($draft === null) {
                throw new ClosingRecapNotReady('No sales are prepared for the current version. Prepare them first.');
            }

            $push = SalesPush::fromClosingRecap($draft->payload);

            $draft->update([
                'status' => ClosingSalesPush::STATUS_SENT,
                'sales_push_id' => $push->id,
                'sent_at' => now(),
            ]);

            AuditLog::record(
                action: AuditAction::ClosingSalesSent,
                auditable: $recap,
                metadata: ['sequence' => $recap->sequence, 'sales_num' => $push->sales_num, 'sales_push' => $push->public_id],
            );

            PushSalesData::dispatch($push->id, $push->sales_num)->afterCommit();

            return $push;
        });
    }
}
