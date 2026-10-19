<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\CoaAccount;
use App\Domain\Canonical\CoaAccountPage;
use App\Domain\Canonical\CoaAccountQuery;
use App\Integrations\Contracts\CoaAccountSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\CoaAccountMapper;

/**
 * Adapter for the ESB Core chart of accounts, read-only ([ESB-025]).
 */
class CoaAccountClient implements CoaAccountSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/accounting/coa';

    public function __construct(
        private readonly EsbClient $client,
        private readonly CoaAccountMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * The vendor's list is "all active" and unpaginated; the search, level
     * and page are applied here.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-coa/get
     */
    public function listAccounts(CoaAccountQuery $query): CoaAccountPage
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH, operation: 'coa.list');

        return CoaAccountPage::fromChart($this->mapper->summariesFromRows($envelope->resultArray()), $query);
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-coa-coaNo/get
     */
    public function findAccount(string $coaNo): ?CoaAccount
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.rawurlencode($coaNo), operation: 'coa.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
