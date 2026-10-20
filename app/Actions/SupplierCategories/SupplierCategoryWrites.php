<?php

namespace App\Actions\SupplierCategories;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordInUse;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\SupplierCategoryDraft;
use App\Integrations\Contracts\SupplierCategorySource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Writes on an existing supplier category ([ESB-023], S2). The rules are
 * RecordWrites'.
 */
class SupplierCategoryWrites
{
    public function __construct(
        private readonly SupplierCategorySource $categories,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'supplier-category',
            audits: [
                RecordOperation::Delete->value => AuditAction::SupplierCategoryDeleted,
                RecordOperation::Restore->value => AuditAction::SupplierCategoryRestored,
                RecordOperation::Create->value => AuditAction::SupplierCategoryCreated,
                RecordOperation::Update->value => AuditAction::SupplierCategoryUpdated,
            ],
            writeUnconfirmed: AuditAction::SupplierCategoryWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws RecordInUse when the ERP says the category is still used
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(int $id): void
    {
        // The pre-check is a read: it runs before the guarded write, so its
        // own failure is never mistaken for an unconfirmed delete.
        if (! $this->categories->canDeleteCategory($id)) {
            throw new RecordInUse;
        }

        $this->writes->run(self::record(), $this->categories->provider(), $id, RecordOperation::Delete, fn () => $this->categories->deleteCategory($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function restore(int $id): void
    {
        $this->writes->run(self::record(), $this->categories->provider(), $id, RecordOperation::Restore, fn () => $this->categories->restoreCategory($id));
    }

    /**
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(SupplierCategoryDraft $draft): ?int
    {
        $id = $this->writes->create(self::record(), $this->categories->provider(), fn () => $this->categories->createCategory($draft), ['name' => $draft->name]);

        return is_int($id) ? $id : null;
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(int $id, SupplierCategoryDraft $draft): void
    {
        $this->writes->run(self::record(), $this->categories->provider(), $id, RecordOperation::Update, fn () => $this->categories->updateCategory($id, $draft));
    }
}
