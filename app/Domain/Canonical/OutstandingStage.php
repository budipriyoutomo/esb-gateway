<?php

namespace App\Domain\Canonical;

/**
 * Where an outstanding purchase request item stands along PR → PO → goods
 * receipt (ADR-034). The value is the report's filter and CSV key.
 */
enum OutstandingStage: string
{
    /** Asked for, not (fully) made into a purchase order yet. */
    case NotOrdered = 'not-ordered';

    /** On an open purchase order, nothing received yet. */
    case NotReceived = 'not-received';

    /** On an open purchase order, part received. */
    case PartiallyReceived = 'partially-received';

    /** Received in full against the order, but not the quantity requested. */
    case NotAsRequested = 'not-as-requested';

    public function label(): string
    {
        return match ($this) {
            self::NotOrdered => 'Not ordered',
            self::NotReceived => 'Not received',
            self::PartiallyReceived => 'Partially received',
            self::NotAsRequested => 'Not as requested',
        };
    }
}
