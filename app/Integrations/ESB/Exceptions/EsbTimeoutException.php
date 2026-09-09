<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The ESB did not answer within our timeout budget.
 *
 * For a write this is the dangerous case: the ESB may have committed the
 * change. No idempotency key is documented, so writes are never retried
 * automatically -- reconcile with a GET by document number instead
 * (docs/05-api-integration.md).
 */
class EsbTimeoutException extends EsbException
{
    public function kind(): string
    {
        return 'timeout';
    }

    public function isRetryable(): bool
    {
        return true;
    }

    protected function userFacingSummary(): string
    {
        return 'The ERP service did not respond in time.';
    }
}
