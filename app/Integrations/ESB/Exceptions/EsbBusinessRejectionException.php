<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The ESB understood the request and refused it on business grounds --
 * a duplicate code, a closed period, a budget that would go negative.
 *
 * Never retry: the answer will not change.
 */
class EsbBusinessRejectionException extends EsbException
{
    public function kind(): string
    {
        return 'business_rejection';
    }

    protected function userFacingSummary(): string
    {
        return 'The ERP service declined this operation.';
    }
}
