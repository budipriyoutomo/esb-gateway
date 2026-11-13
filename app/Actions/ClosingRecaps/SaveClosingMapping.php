<?php

namespace App\Actions\ClosingRecaps;

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPurposeMapping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Save or remove one closing mapping, audited in the same transaction
 * (ADR-032). The audit row keeps the values before and after, because these
 * mappings decide what is later written to the ERP.
 */
class SaveClosingMapping
{
    /**
     * @param  array{branchId: int, branchCode: string, branchName: string, locationId: int, locationName: string}  $values
     */
    public function outlet(string $outletCode, array $values, ?int $userId): ClosingOutletMapping
    {
        return $this->save(
            ClosingOutletMapping::query()->firstOrNew(['outlet_code' => $outletCode]),
            'outlet',
            $outletCode,
            [
                'branch_id' => $values['branchId'],
                'branch_code' => $values['branchCode'],
                'branch_name' => $values['branchName'],
                'location_id' => $values['locationId'],
                'location_name' => $values['locationName'],
                'updated_by' => $userId,
            ],
        );
    }

    /**
     * @param  array{esbMenuId: int|null, esbMenuCode: string|null, esbMenuName: string|null, bomId: int|null, bomName: string|null}  $values
     */
    public function item(string $source, string $key, array $values, ?int $userId): ClosingItemMapping
    {
        return $this->save(
            ClosingItemMapping::query()->firstOrNew(['source' => $source, 'item_key' => $key]),
            'item',
            $source.':'.$key,
            [
                'esb_menu_id' => $values['esbMenuId'],
                'esb_menu_code' => $values['esbMenuId'] === null ? null : $values['esbMenuCode'],
                'esb_menu_name' => $values['esbMenuId'] === null ? null : $values['esbMenuName'],
                'bom_id' => $values['bomId'],
                'bom_name' => $values['bomId'] === null ? null : $values['bomName'],
                'updated_by' => $userId,
            ],
        );
    }

    /**
     * @param  array{purposeId: int, purposeName: string, direction: int}  $values
     */
    public function purpose(string $kind, array $values, ?int $userId): ClosingPurposeMapping
    {
        return $this->save(
            ClosingPurposeMapping::query()->firstOrNew(['kind' => $kind]),
            'purpose',
            $kind,
            [
                'purpose_id' => $values['purposeId'],
                'purpose_name' => $values['purposeName'],
                'direction' => $values['direction'],
                'updated_by' => $userId,
            ],
        );
    }

    public function delete(ClosingOutletMapping|ClosingItemMapping|ClosingPurposeMapping $mapping, string $type, string $key): void
    {
        DB::transaction(function () use ($mapping, $type, $key): void {
            $before = $mapping->toView();
            $mapping->delete();

            AuditLog::record(
                action: AuditAction::ClosingMappingDeleted,
                auditable: $mapping,
                metadata: ['type' => $type, 'key' => $key, 'before' => $before],
            );
        });
    }

    /**
     * @template TMapping of ClosingOutletMapping|ClosingItemMapping|ClosingPurposeMapping
     *
     * @param  TMapping  $mapping
     * @param  array<string, mixed>  $attributes
     * @return TMapping
     */
    private function save(Model $mapping, string $type, string $key, array $attributes): Model
    {
        return DB::transaction(function () use ($mapping, $type, $key, $attributes): Model {
            $before = $mapping->exists ? $mapping->toView() : null;

            $mapping->fill($attributes)->save();

            AuditLog::record(
                action: AuditAction::ClosingMappingSaved,
                auditable: $mapping,
                metadata: ['type' => $type, 'key' => $key, 'before' => $before, 'after' => $mapping->toView()],
            );

            return $mapping;
        });
    }
}
