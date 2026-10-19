<?php

namespace App\Domain\Canonical;

/**
 * An approval decision on any approvable document (purchase request,
 * purchase invoice, purchase order, simple purchase, ...).
 *
 * The gateway's own vocabulary; each adapter maps it onto the ESB's path
 * segments rather than relying on the values matching. Where a rejection
 * carries a reason, the reason is passed beside the decision rather than
 * folded into it.
 */
enum ApprovalDecision: string
{
    case Authorize = 'authorize';
    case Reject = 'reject';
}
