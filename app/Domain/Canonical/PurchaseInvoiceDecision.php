<?php

namespace App\Domain\Canonical;

/**
 * An approval decision on a purchase invoice.
 *
 * The gateway's own vocabulary; the adapter maps it onto the ESB's path
 * segments rather than relying on the values matching.
 */
enum PurchaseInvoiceDecision: string
{
    case Authorize = 'authorize';
    case Reject = 'reject';
}
