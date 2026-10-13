<?php

namespace App\Actions\SalesPushes;

use App\Audit\AuditAction;
use App\Jobs\PushSalesData;
use App\Models\AuditLog;
use App\Models\SalesPush;

/**
 * Queue a failed or rejected push again, on behalf of a person.
 *
 * Refused when a newer push of the same salesNum exists: sending this one
 * would overwrite the newer data in the ESB.
 */
class RetrySalesPush
{
    /**
     * @return bool Whether the push was queued.
     */
    public function handle(SalesPush $push): bool
    {
        if (! $push->canBeRetried()) {
            return false;
        }

        $previousStatus = $push->status;

        $push->update(['status' => SalesPush::STATUS_QUEUED]);

        AuditLog::record(
            action: AuditAction::SalesPushRetried,
            auditable: $push,
            metadata: [
                'sales_num' => $push->sales_num,
                'previous_status' => $previousStatus,
            ],
        );

        PushSalesData::dispatch($push->id, $push->sales_num)->afterCommit();

        return true;
    }
}
