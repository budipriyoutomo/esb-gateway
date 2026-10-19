<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\Purpose;
use App\Domain\Canonical\PurposePage;
use App\Domain\Canonical\PurposeQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for transaction purposes. Read-only for now;
 * create, update, and delete are not built ([ESB-015]).
 *
 * App\Integrations\ESB\Clients\PurposeClient is the reference implementation;
 * callers depend on this contract (ADR-016, ADR-019).
 */
interface PurposeSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listPurposes(PurposeQuery $query): PurposePage;

    /**
     * The purpose with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findPurpose(int $id): ?Purpose;
}
