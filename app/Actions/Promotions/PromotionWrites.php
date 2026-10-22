<?php

namespace App\Actions\Promotions;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PromotionDraft;
use App\Integrations\Contracts\PromotionSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Create POS promotions ([ESB-050]). The rules are RecordWrites': one
 * create per person at a time, one attempt, audited with the person.
 */
class PromotionWrites
{
    public function __construct(
        private readonly PromotionSource $promotions,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'promotion',
            audits: [RecordOperation::Create->value => AuditAction::PromotionCreated],
            writeUnconfirmed: AuditAction::PromotionWriteUnconfirmed,
        );
    }

    /**
     * @return int|null The new id, when the ERP gave one.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(PromotionDraft $draft): ?int
    {
        $id = $this->writes->create(
            self::record(),
            $this->promotions->provider(),
            fn (): ?int => $this->promotions->createPromotion($draft),
            ['type' => $draft->type->value, 'code' => $draft->promotionCode, 'notes' => $draft->notes],
        );

        return is_int($id) ? $id : null;
    }
}
