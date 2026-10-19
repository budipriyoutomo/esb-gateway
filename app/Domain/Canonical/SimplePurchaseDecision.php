<?php

namespace App\Domain\Canonical;

/**
 * An approval decision on a simple purchase.
 *
 * The gateway's own vocabulary; the adapter maps it onto the ESB's path
 * segments rather than relying on the values matching. As on purchase
 * orders, a rejection carries a reason, which is passed beside the decision
 * rather than folded into it.
 */
enum SimplePurchaseDecision: string
{
    case Authorize = 'authorize';
    case Reject = 'reject';
}
