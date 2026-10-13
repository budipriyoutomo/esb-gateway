<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\SalesDataReceipt;
use App\Integrations\Contracts\SalesDataSink;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Adapter for ESB OMS Push Sales Data V2 ([ESB-011]).
 *
 * The body is forwarded as received. Validating the transaction is the ESB's
 * job, and it answers with a list of what it refused.
 */
class SalesDataClient implements SalesDataSink
{
    public const PROVIDER = 'esb';

    /**
     * The vendor documents this operation on the FnB backend hosts.
     */
    private const FAMILY = EsbFamily::FnbBackend;

    private const PATH = '/extv1/push/sales-data';

    /**
     * Longest provider value kept on the receipt.
     */
    private const MAX_VALUE_LENGTH = 32;

    public function __construct(private readonly EsbClient $client) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     * @throws EsbConfigurationException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Push_Sales_Data-push-sales-data-v2
     */
    public function pushSalesData(array $salesData): SalesDataReceipt
    {
        $envelope = $this->client->post(
            self::FAMILY,
            self::PATH,
            $salesData,
            operation: 'sales-data.push',
        );

        $result = $envelope->resultArray();

        return new SalesDataReceipt(
            status: $this->shortString($result['status'] ?? null),
            syncDate: $this->shortString($result['syncDate'] ?? null),
        );
    }

    /**
     * ESB responses are untrusted: keep a short scalar, drop anything else.
     */
    private function shortString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, self::MAX_VALUE_LENGTH);
    }
}
