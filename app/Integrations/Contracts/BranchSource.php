<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\BranchDetail;
use App\Domain\Canonical\BranchPage;
use App\Domain\Canonical\BranchQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for branches. Read-only: the ESB documents no
 * branch create or update ([ESB-051]).
 *
 * App\Integrations\ESB\Clients\BranchClient is the reference implementation;
 * callers depend on this contract (ADR-016, ADR-019).
 */
interface BranchSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listBranches(BranchQuery $query): BranchPage;

    /**
     * The branch with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findBranch(int $id): ?BranchDetail;
}
