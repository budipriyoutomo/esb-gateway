<?php

namespace App\Domain\Canonical;

/**
 * Which side of the ledger to read ([ESB-042]); none is both. The values
 * are the gateway's; the vendor's debitCreditFilter is 1 / 2.
 */
enum LedgerSide: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
