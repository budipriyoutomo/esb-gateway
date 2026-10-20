<?php

namespace App\Actions\Products;

use App\Actions\Support\CreatedRecord;
use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\ProductDetail;
use App\Domain\Canonical\ProductDraft;
use App\Integrations\Contracts\ProductSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete or restore an existing product ([ESB-029], S2). The rules are
 * RecordWrites'.
 */
class ProductWrites
{
    public function __construct(
        private readonly ProductSource $products,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'product',
            audits: [
                RecordOperation::Delete->value => AuditAction::ProductDeleted,
                RecordOperation::Restore->value => AuditAction::ProductRestored,
                RecordOperation::Create->value => AuditAction::ProductCreated,
                RecordOperation::Update->value => AuditAction::ProductUpdated,
            ],
            writeUnconfirmed: AuditAction::ProductWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(int $id): void
    {
        $this->writes->run(self::record(), $this->products->provider(), $id, RecordOperation::Delete, fn () => $this->products->deleteProduct($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function restore(int $id): void
    {
        $this->writes->run(self::record(), $this->products->provider(), $id, RecordOperation::Restore, fn () => $this->products->restoreProduct($id));
    }

    /**
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(ProductDraft $draft): CreatedRecord
    {
        $created = $this->writes->create(self::record(), $this->products->provider(), fn () => $this->products->createProduct($draft), [
            'name' => $draft->name,
            'units' => count($draft->units),
        ]);

        return $created instanceof CreatedRecord ? $created : new CreatedRecord(null);
    }

    /**
     * @param  ProductDetail  $current  The product as the ERP has it, read before the write.
     *
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(int $id, ProductDraft $draft, ProductDetail $current): void
    {
        $this->writes->run(self::record(), $this->products->provider(), $id, RecordOperation::Update, function () use ($id, $draft, $current): array {
            $this->products->updateProduct($id, $draft, $current);

            return ['units' => count($draft->units)];
        });
    }
}
