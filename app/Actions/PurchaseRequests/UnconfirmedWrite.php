<?php

namespace App\Actions\PurchaseRequests;

use App\Audit\AuditAction;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Audits a create, update, delete, or import the ESB did not confirm.
 */
final class UnconfirmedWrite
{
    /**
     * @param  'create'|'update'|'delete'|'import'  $operation
     * @param  array<string, mixed>  $details
     */
    public static function record(string $provider, string $operation, EsbException $exception, array $details = []): void
    {
        AuditLog::record(
            action: AuditAction::PurchaseRequestWriteUnconfirmed,
            metadata: [
                'provider' => $provider,
                'operation' => $operation,
                ...$details,
                'failure' => $exception->kind(),
                'correlation_id' => $exception->context->correlationId,
            ],
        );
    }
}
