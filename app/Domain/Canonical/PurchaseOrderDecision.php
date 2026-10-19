<?php

namespace App\Domain\Canonical;

/**
 * An approval decision on a purchase order.
 *
 * The gateway's own vocabulary; the adapter maps it onto the ESB's path
 * segments rather than relying on the values matching. Unlike the purchase
 * request and invoice decisions, a rejection carries a reason, which is
 * passed beside the decision rather than folded into it.
 */
enum PurchaseOrderDecision: string
{
    case Authorize = 'authorize';
    case Reject = 'reject';
}
