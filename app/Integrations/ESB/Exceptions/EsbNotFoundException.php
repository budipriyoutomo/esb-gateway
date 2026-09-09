<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The requested record, or a record it references, does not exist in the ERP.
 */
class EsbNotFoundException extends EsbException
{
    public function kind(): string
    {
        return 'not_found';
    }

    protected function userFacingSummary(): string
    {
        return 'The requested record was not found in the ERP service.';
    }
}
