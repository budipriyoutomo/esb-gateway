<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PurchaseRequestDraft;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Create a purchase request in the ERP, on behalf of a person.
 */
class CreatePurchaseRequest
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @return string|null The new document number, when the ERP returned one.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(PurchaseRequestDraft $draft): ?string
    {
        $provider = $this->purchaseRequests->provider();

        $number = $this->guard->run(
            PurchaseRequestLocks::create(Auth::id()),
            fn (): ?string => $this->purchaseRequests->createPurchaseRequest($draft),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'create', $exception, [
                'number' => null,
                'branch_id' => $draft->branchId,
                'lines' => count($draft->lines),
            ]),
        );

        AuditLog::record(
            action: AuditAction::PurchaseRequestCreated,
            metadata: [
                'provider' => $provider,
                'number' => $number,
                'branch_id' => $draft->branchId,
                'lines' => count($draft->lines),
            ],
        );

        return $number;
    }
}
