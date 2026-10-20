<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\CoaAccount;
use App\Domain\Canonical\CoaAccountPage;
use App\Domain\Canonical\CoaAccountQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for its chart of accounts. Read-only
 * ([ESB-025]).
 */
interface CoaAccountSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listAccounts(CoaAccountQuery $query): CoaAccountPage;

    /**
     * The account with this number, or null when the provider answers
     * successfully with none. Callers pass a number already held to the
     * route's pattern.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findAccount(string $coaNo): ?CoaAccount;

    /**
     * Deactivate an account and its active sub-accounts (the vendor's
     * soft delete). One attempt.
     *
     * @throws EsbException
     */
    public function deactivateAccount(string $coaNo): void;

    /**
     * Reactivate a deactivated account. One attempt.
     *
     * @throws EsbException
     */
    public function restoreAccount(string $coaNo): void;

    /**
     * Move an account one place up or down among its siblings. One attempt.
     *
     * @param  'up'|'down'  $direction
     *
     * @throws EsbException
     */
    public function moveAccount(string $coaNo, string $direction): void;
}
