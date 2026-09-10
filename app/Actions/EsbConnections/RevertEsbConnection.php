<?php

namespace App\Actions\EsbConnections;

use App\Audit\AuditAction;
use App\Integrations\ESB\Auth\TokenManager;
use App\Integrations\ESB\EsbFamily;
use App\Models\AuditLog;
use App\Models\EsbConnection;
use Illuminate\Support\Facades\DB;

/**
 * Drop the runtime override for one ESB family, handing it back to
 * config/esb.php (ADR-018).
 *
 * This is the way out of a bad edit that does not need a deploy, so it is
 * audited as carefully as the edit itself.
 */
class RevertEsbConnection
{
    public function __construct(private readonly TokenManager $tokenManager) {}

    public function handle(EsbFamily $family, string $environment): bool
    {
        $connection = EsbConnection::query()
            ->where('family', $family->value)
            ->where('environment', $environment)
            ->first();

        if ($connection === null) {
            return false;
        }

        $hadCredential = $connection->hasCredential();

        return DB::transaction(function () use ($connection, $family, $environment, $hadCredential): bool {
            AuditLog::record(
                action: AuditAction::EsbConnectionReverted,
                metadata: [
                    'family' => $family->value,
                    'environment' => $environment,
                    'had_credential' => $hadCredential,
                ],
            );

            $connection->delete();

            if ($hadCredential) {
                // Configuration may supply a different credential than the row
                // did, so a token minted from the row must not outlive it.
                $this->tokenManager->forget();
            }

            return true;
        });
    }
}
