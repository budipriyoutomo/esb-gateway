<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The ESB credential is valid but lacks permission for this operation.
 *
 * Retrying will not help -- the ESB-side privileges need changing.
 */
class EsbAuthorizationException extends EsbException
{
    public function kind(): string
    {
        return 'authorization';
    }

    protected function userFacingSummary(): string
    {
        return 'The gateway is not permitted to perform this operation in the ERP service.';
    }
}
