<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The response was not JSON, or carried no `code` field.
 *
 * ESB responses are untrusted input (docs/10-security.md); a body we cannot
 * parse is never guessed at.
 */
class EsbMalformedResponseException extends EsbException
{
    public function kind(): string
    {
        return 'malformed_response';
    }

    protected function userFacingSummary(): string
    {
        return 'The ERP service returned a response the gateway could not read.';
    }
}
