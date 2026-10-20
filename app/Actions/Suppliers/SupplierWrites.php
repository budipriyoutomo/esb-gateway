<?php

namespace App\Actions\Suppliers;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\SupplierSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Deactivate or restore an existing record ([ESB-024], S2). The rules are
 * RecordWrites'.
 */
class SupplierWrites
{
    public function __construct(
        private readonly SupplierSource $suppliers,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'supplier',
            audits: [
                RecordOperation::Deactivate->value => AuditAction::SupplierDeactivated,
                RecordOperation::Restore->value => AuditAction::SupplierRestored,
                RecordOperation::Attach->value => AuditAction::SupplierAttachmentsUploaded,
                RecordOperation::Detach->value => AuditAction::SupplierAttachmentsDeleted,
            ],
            writeUnconfirmed: AuditAction::SupplierWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function deactivate(int $id): void
    {
        $this->writes->run(self::record(), $this->suppliers->provider(), $id, RecordOperation::Deactivate, fn () => $this->suppliers->deactivateSupplier($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function restore(int $id): void
    {
        $this->writes->run(self::record(), $this->suppliers->provider(), $id, RecordOperation::Restore, fn () => $this->suppliers->restoreSupplier($id));
    }

    /**
     * The audit identifies each file by name, size and SHA-256; the gateway
     * keeps no copy.
     *
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function attach(int $id, array $files): void
    {
        $this->writes->run(self::record(), $this->suppliers->provider(), $id, RecordOperation::Attach, function () use ($id, $files): array {
            $this->suppliers->attachFiles($id, $files);

            return ['files' => array_map(static fn (array $file): array => [
                'filename' => $file['filename'],
                'bytes' => strlen($file['contents']),
                'sha256' => hash('sha256', $file['contents']),
            ], $files)];
        });
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function deleteAttachments(int $id): void
    {
        $this->writes->run(self::record(), $this->suppliers->provider(), $id, RecordOperation::Detach, fn () => $this->suppliers->deleteAttachments($id));
    }
}
