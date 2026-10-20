<?php

namespace App\Actions\Pricelists;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PricelistDraft;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Propose a pricelist, or edit a proposed one ([ESB-031], S3). The lock
 * key is the one TempPricelistDocument uses, so an edit cannot race an
 * authorize, reject or delete of the same proposal.
 */
class PricelistWrites
{
    public function __construct(
        private readonly PricelistSource $pricelists,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: TempPricelistDocument::approvable()->lockPrefix,
            audits: [
                RecordOperation::Create->value => AuditAction::PricelistCreated,
                RecordOperation::Update->value => AuditAction::PricelistUpdated,
            ],
            writeUnconfirmed: AuditAction::PricelistWriteUnconfirmed,
        );
    }

    /**
     * @param  string  $name  How the person knows it, e.g. "for CV Maju Jaya".
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(PricelistDraft $draft, string $name): void
    {
        $this->writes->create(self::record(), $this->pricelists->provider(), fn () => $this->pricelists->createPricelist($draft), [
            'name' => $name,
            'supplier_id' => $draft->supplierId,
            'lines' => count($draft->lines),
        ]);
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this proposal is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(string $number, PricelistDraft $draft): void
    {
        $this->writes->run(self::record(), $this->pricelists->provider(), $number, RecordOperation::Update, function () use ($number, $draft): array {
            $this->pricelists->updateTempPricelist($number, $draft);

            return ['lines' => count($draft->lines)];
        });
    }
}
