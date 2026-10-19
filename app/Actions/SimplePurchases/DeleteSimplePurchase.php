<?php

namespace App\Actions\SimplePurchases;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Delete a simple purchase in the ERP, on behalf of a person.
 */
class DeleteSimplePurchase
{
    public function __construct(
        private readonly SimplePurchaseSource $simplePurchases,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $provider = $this->simplePurchases->provider();

        $this->guard->run(
            SimplePurchaseLocks::document($number),
            fn () => $this->simplePurchases->deleteSimplePurchase($number),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'delete', $number, $exception),
        );

        AuditLog::record(
            action: AuditAction::SimplePurchaseDeleted,
            metadata: [
                'provider' => $provider,
                'number' => $number,
            ],
        );
    }
}
