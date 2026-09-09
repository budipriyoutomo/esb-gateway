<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The request never reached the ESB -- DNS, TLS, or transport failure.
 *
 * Unlike a timeout, nothing was delivered, so a read can safely be retried.
 */
class EsbConnectionException extends EsbException
{
    public function kind(): string
    {
        return 'connection';
    }

    public function isRetryable(): bool
    {
        return true;
    }

    protected function userFacingSummary(): string
    {
        return 'The gateway could not reach the ERP service.';
    }
}
