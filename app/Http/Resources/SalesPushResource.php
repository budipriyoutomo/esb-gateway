<?php

namespace App\Http\Resources;

use App\Models\SalesPush;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A sales push as the POS sees it through /api/v1 (ADR-022).
 *
 * Never the payload: the POS already has it, and it carries customer and
 * payment details.
 *
 * @mixin SalesPush
 */
class SalesPushResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'salesNum' => $this->sales_num,
            'branchCode' => $this->branch_code,
            'status' => $this->status,
            'attempts' => $this->attempts,
            // The ESB's per-attribute refusals, for the POS to fix.
            'errors' => $this->errors,
            // Quote this when asking for support; it finds the ESB call.
            'reference' => $this->correlation_id,
            'esbSyncDate' => $this->esb_sync_date,
            'pushedAt' => $this->pushed_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
