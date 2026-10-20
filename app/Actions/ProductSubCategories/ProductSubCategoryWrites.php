<?php

namespace App\Actions\ProductSubCategories;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\ProductSubCategoryDraft;
use App\Integrations\Contracts\ProductSubCategorySource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Writes on an existing product sub-category ([ESB-022], S2). The rules are
 * RecordWrites'.
 */
class ProductSubCategoryWrites
{
    public function __construct(
        private readonly ProductSubCategorySource $subCategories,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'product-sub-category',
            audits: [
                RecordOperation::Delete->value => AuditAction::ProductSubCategoryDeleted,
                RecordOperation::Deactivate->value => AuditAction::ProductSubCategoryDeactivated,
                RecordOperation::Restore->value => AuditAction::ProductSubCategoryRestored,
                RecordOperation::Create->value => AuditAction::ProductSubCategoryCreated,
                RecordOperation::Update->value => AuditAction::ProductSubCategoryUpdated,
            ],
            writeUnconfirmed: AuditAction::ProductSubCategoryWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(int $id): void
    {
        $this->writes->run(self::record(), $this->subCategories->provider(), $id, RecordOperation::Delete, fn () => $this->subCategories->deleteSubCategory($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function deactivate(int $id): void
    {
        $this->writes->run(self::record(), $this->subCategories->provider(), $id, RecordOperation::Deactivate, fn () => $this->subCategories->deactivateSubCategory($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function restore(int $id): void
    {
        $this->writes->run(self::record(), $this->subCategories->provider(), $id, RecordOperation::Restore, fn () => $this->subCategories->restoreSubCategory($id));
    }

    /**
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(ProductSubCategoryDraft $draft): void
    {
        $this->writes->create(self::record(), $this->subCategories->provider(), fn () => $this->subCategories->createSubCategory($draft), ['name' => $draft->name]);
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(int $id, ProductSubCategoryDraft $draft): void
    {
        $this->writes->run(self::record(), $this->subCategories->provider(), $id, RecordOperation::Update, fn () => $this->subCategories->updateSubCategory($id, $draft));
    }
}
