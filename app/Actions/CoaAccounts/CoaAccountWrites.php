<?php

namespace App\Actions\CoaAccounts;

use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\CoaAccountSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Deactivate or restore an existing record ([ESB-025], S2). The rules are
 * RecordWrites'.
 */
class CoaAccountWrites
{
    public function __construct(
        private readonly CoaAccountSource $accounts,
        private readonly RecordWrites $writes,
    ) {}

    public static function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'coa',
            audits: [
                RecordOperation::Deactivate->value => AuditAction::CoaDeactivated,
                RecordOperation::Restore->value => AuditAction::CoaRestored,
                RecordOperation::Move->value => AuditAction::CoaMoved,
            ],
            writeUnconfirmed: AuditAction::CoaWriteUnconfirmed,
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function deactivate(string $id): void
    {
        $this->writes->run(self::record(), $this->accounts->provider(), $id, RecordOperation::Deactivate, fn () => $this->accounts->deactivateAccount($id));
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function restore(string $id): void
    {
        $this->writes->run(self::record(), $this->accounts->provider(), $id, RecordOperation::Restore, fn () => $this->accounts->restoreAccount($id));
    }

    /**
     * @param  'up'|'down'  $direction
     *
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function move(string $id, string $direction): void
    {
        $this->writes->run(self::record(), $this->accounts->provider(), $id, RecordOperation::Move, function () use ($id, $direction): array {
            $this->accounts->moveAccount($id, $direction);

            return ['direction' => $direction];
        });
    }
}
