<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * A recognised failure with no more specific classification, or a status code
 * the vendor has not documented.
 *
 * Deliberately not retryable: we do not know what happened, so we do not know
 * that repeating it is safe.
 */
class EsbUnknownException extends EsbException
{
    public function kind(): string
    {
        return 'unknown';
    }
}
