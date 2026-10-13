<?php

namespace App\Domain\Canonical;

/**
 * An approval decision on a purchase request.
 *
 * The values are the gateway's own vocabulary. They happen to match the ESB's
 * path segments (`/authorize`, `/reject`), but the adapter maps them rather
 * than relying on that.
 */
enum PurchaseRequestDecision: string
{
    case Authorize = 'authorize';
    case Reject = 'reject';
}
