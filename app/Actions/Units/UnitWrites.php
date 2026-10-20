<?php

namespace App\Actions\Units;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\UnitOfMeasureDraft;
use App\Integrations\Contracts\UnitOfMeasureSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Writes on an existing unit ([ESB-020], S2). The rules are
 * RecordWrites'.
 */
class UnitWrites
{
    public function __construct(
        private readonly UnitOfMeasureSource $units,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'unit',
            audits: [
                RecordOperation::Delete->value => AuditAction::UnitDeleted,
                RecordOperation::Restore->value => AuditAction::UnitRestored,
                RecordOperation::Create->value => AuditAction::UnitCreated,
                RecordOperation::Update->value => AuditAction::UnitUpdated,
            ],
            writeUnconfirmed: AuditAction::UnitWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(int $id): void
    {
        $this->writes->run(self::record(), $this->units->provider(), $id, RecordOperation::Delete, function () use ($id): array {
            // Whether the ERP removed the unit or only deactivated it.
            $hard = $this->units->deleteUnit($id);

            return $hard === null ? [] : ['hardDelete' => $hard];
        });
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function restore(int $id): void
    {
        $this->writes->run(self::record(), $this->units->provider(), $id, RecordOperation::Restore, fn () => $this->units->restoreUnit($id));
    }

    /**
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(UnitOfMeasureDraft $draft): void
    {
        $this->writes->create(self::record(), $this->units->provider(), fn () => $this->units->createUnit($draft), ['name' => $draft->name]);
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(int $id, UnitOfMeasureDraft $draft): void
    {
        $this->writes->run(self::record(), $this->units->provider(), $id, RecordOperation::Update, fn () => $this->units->updateUnit($id, $draft));
    }
}
