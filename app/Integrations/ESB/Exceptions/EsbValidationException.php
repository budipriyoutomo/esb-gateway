<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The ESB rejected the request as invalid. Our request was wrong, not the ESB.
 */
class EsbValidationException extends EsbException
{
    public function kind(): string
    {
        return 'validation';
    }

    protected function userFacingSummary(): string
    {
        return 'The ERP service rejected this request as invalid.';
    }
}
