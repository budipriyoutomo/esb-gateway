<?php

namespace App\Integrations\ESB\Exceptions;

/**
 * The ESB did not accept our credentials or token.
 *
 * Because the ESB permits only one active session per credential (ADR-009),
 * this can also mean another process or a human logged in with the same
 * credentials and invalidated our token.
 */
class EsbAuthenticationException extends EsbException
{
    public function kind(): string
    {
        return 'authentication';
    }

    public function shouldInvalidateToken(): bool
    {
        return true;
    }

    protected function userFacingSummary(): string
    {
        return 'The gateway could not authenticate with the ERP service.';
    }
}
