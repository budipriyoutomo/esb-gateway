<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The ESB returned a server error, or an envelope that could not be classified.
 */
class EsbUpstreamException extends EsbException
{
    public function kind(): string
    {
        return 'upstream';
    }

    public function isRetryable(): bool
    {
        return true;
    }

    protected function userFacingSummary(): string
    {
        return 'The ERP service reported an internal error.';
    }
}
