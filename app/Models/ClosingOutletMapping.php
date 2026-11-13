<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Outlet code -> ERP branch and location (ADR-032).
 *
 * @property int $id
 * @property string $outlet_code
 * @property int $branch_id
 * @property string $branch_code
 * @property string $branch_name
 * @property int $location_id
 * @property string $location_name
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingOutletMapping extends Model
{
    /** CL + branch code + YYYYMMDD must fit the ESB's 20-character salesNum. */
    public const MAX_BRANCH_CODE = 10;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'branch_id' => 'integer',
            'location_id' => 'integer',
        ];
    }

    /**
     * @return array{branchId: int, branchCode: string, branchName: string, locationId: int, locationName: string}
     */
    public function toView(): array
    {
        return [
            'branchId' => $this->branch_id,
            'branchCode' => $this->branch_code,
            'branchName' => $this->branch_name,
            'locationId' => $this->location_id,
            'locationName' => $this->location_name,
        ];
    }
}
