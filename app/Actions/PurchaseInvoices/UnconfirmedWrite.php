<?php

namespace App\Actions\PurchaseInvoices;

use App\Audit\AuditAction;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Audits a purchase invoice decision or delete the ESB did not confirm.
 */
final class UnconfirmedWrite
{
    /**
     * @param  'authorize'|'reject'|'delete'  $operation
     */
    public static function record(string $provider, string $operation, string $number, EsbException $exception): void
    {
        AuditLog::record(
            action: AuditAction::PurchaseInvoiceWriteUnconfirmed,
            metadata: [
                'provider' => $provider,
                'operation' => $operation,
                'number' => $number,
                'failure' => $exception->kind(),
                'correlation_id' => $exception->context->correlationId,
            ],
        );
    }
}
