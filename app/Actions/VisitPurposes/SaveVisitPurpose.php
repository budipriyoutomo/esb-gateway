<?php

namespace App\Actions\VisitPurposes;

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\VisitPurpose;
use Illuminate\Support\Facades\DB;

/**
 * Add, change or remove a visit purpose kept in the gateway, audited in the
 * same transaction with the values before and after ([ESB-052]).
 */
class SaveVisitPurpose
{
    /**
     * @param  array{name: string, notes: string|null, active: bool}  $values
     */
    public function save(VisitPurpose $purpose, array $values, ?int $userId): VisitPurpose
    {
        return DB::transaction(function () use ($purpose, $values, $userId): VisitPurpose {
            $before = $purpose->exists ? $purpose->toView() : null;

            $purpose->fill([
                'name' => $values['name'],
                'name_key' => VisitPurpose::keyOf($values['name']),
                'notes' => $values['notes'],
                'is_active' => $values['active'],
                'updated_by' => $userId,
            ])->save();

            AuditLog::record(
                action: AuditAction::VisitPurposeSaved,
                auditable: $purpose,
                metadata: ['before' => $before, 'after' => $purpose->toView()],
            );

            return $purpose;
        });
    }

    public function delete(VisitPurpose $purpose): void
    {
        DB::transaction(function () use ($purpose): void {
            $before = $purpose->toView();
            $purpose->delete();

            AuditLog::record(
                action: AuditAction::VisitPurposeDeleted,
                auditable: $purpose,
                metadata: ['before' => $before],
            );
        });
    }
}
