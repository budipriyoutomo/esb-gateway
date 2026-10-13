<?php

namespace App\Domain\Canonical;

/**
 * What a provider said when it accepted a sales transaction.
 *
 * Both values are the provider's own and are kept for display only. The ESB
 * documents "00" as the only status value; what any other value means is TBD
 * (docs/05-api-integration.md, [ESB-011]).
 */
readonly class SalesDataReceipt
{
    public function __construct(
        public ?string $status,
        public ?string $syncDate,
    ) {}
}
