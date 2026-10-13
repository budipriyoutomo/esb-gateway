<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The gateway could not complete the exchange with the ESB -- DNS, TLS, or
 * transport failure.
 *
 * Usually nothing was delivered, so a read can safely be retried. A write is
 * treated more cautiously: see mayHaveBeenApplied().
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

    /**
     * cURL reports a reset or an empty reply after the request
     * was sent the same way as a refused connection, so a write cannot be
     * assumed undelivered.
     */
    public function mayHaveBeenApplied(): bool
    {
        return true;
    }
}
