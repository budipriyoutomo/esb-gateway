<?php

namespace App\Actions\Support;

use RuntimeException;

/**
 * Another write under the same lock has not finished yet.
 *
 * Nothing was sent to the ESB. The caller should let the user look at the
 * current state again rather than resubmit.
 */
class WriteAlreadyInFlight extends RuntimeException
{
    public function __construct(public readonly string $lockKey)
    {
        parent::__construct(sprintf('A write under "%s" is already in progress.', $lockKey));
    }
}
