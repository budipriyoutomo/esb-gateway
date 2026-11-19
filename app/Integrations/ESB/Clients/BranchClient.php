<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\BranchDetail;
use App\Domain\Canonical\BranchPage;
use App\Domain\Canonical\BranchQuery;
use App\Integrations\Contracts\BranchSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\BranchMapper;

/**
 * Adapter for ESB Core Branch, read-only ([ESB-051]).
 *
 * The list reads GET /branch, which returns every branch at once (id, code,
 * name). GET /branch/list pages on the ESB but documents no row shape, so it
 * is not used. No Service wraps this: it applies no business rule.
 */
class BranchClient implements BranchSource
{
    public const PROVIDER = 'esb';

    /**
     * The Master - Company - Branch group is documented under the Core base
     * URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/branch';

    public function __construct(
        private readonly EsbClient $client,
        private readonly BranchMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/branch/get
     */
    public function listBranches(BranchQuery $query): BranchPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            // Without a status, inactive branches are asked for too. With
            // one, only flagActive is sent: whether flagActive=0 also needs
            // flagIncludeNotActive=1 is undocumented (TBD).
            $query->activeOnly === null
                ? ['flagIncludeNotActive' => 1]
                : ['flagActive' => (int) $query->activeOnly],
            operation: 'branch.list',
        );

        return BranchPage::fromAll($this->mapper->summariesFromResult($envelope->resultArray()), $query);
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/branch-branchID/get
     */
    public function findBranch(int $id): ?BranchDetail
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.$id,
            operation: 'branch.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
