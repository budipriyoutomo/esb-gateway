<?php

namespace App\Actions\ProductCategories;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\ProductCategoryDraft;
use App\Integrations\Contracts\ProductCategorySource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Writes on an existing product category ([ESB-021], S2). The rules are
 * RecordWrites'.
 */
class ProductCategoryWrites
{
    public function __construct(
        private readonly ProductCategorySource $categories,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'product-category',
            audits: [
                RecordOperation::Delete->value => AuditAction::ProductCategoryDeleted,
                RecordOperation::Deactivate->value => AuditAction::ProductCategoryDeactivated,
                RecordOperation::Restore->value => AuditAction::ProductCategoryRestored,
                RecordOperation::Create->value => AuditAction::ProductCategoryCreated,
                RecordOperation::Update->value => AuditAction::ProductCategoryUpdated,
            ],
            writeUnconfirmed: AuditAction::ProductCategoryWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(int $id): void
    {
        $this->writes->run(self::record(), $this->categories->provider(), $id, RecordOperation::Delete, fn () => $this->categories->deleteCategory($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function deactivate(int $id): void
    {
        $this->writes->run(self::record(), $this->categories->provider(), $id, RecordOperation::Deactivate, fn () => $this->categories->deactivateCategory($id));
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
    public function create(ProductCategoryDraft $draft): void
    {
        $this->writes->create(self::record(), $this->categories->provider(), fn () => $this->categories->createCategory($draft), ['name' => $draft->name]);
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(int $id, ProductCategoryDraft $draft): void
    {
        $this->writes->run(self::record(), $this->categories->provider(), $id, RecordOperation::Update, fn () => $this->categories->updateCategory($id, $draft));
    }
}
