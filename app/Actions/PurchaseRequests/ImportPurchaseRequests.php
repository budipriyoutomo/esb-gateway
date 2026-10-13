<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Hand the ERP a spreadsheet of purchase requests, on behalf of a person.
 *
 * The gateway neither parses nor stores the file: its layout is the ERP's own
 * and undocumented. The audit row identifies it by name, size and SHA-256, so
 * what was sent can be matched to a file someone still has. Importing the same
 * file twice may create every request twice, which is why an unconfirmed
 * import is audited and reported as "check before retrying".
 */
class ImportPurchaseRequests
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when this person's previous import is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $contents, string $filename): void
    {
        $provider = $this->purchaseRequests->provider();

        $file = [
            'filename' => $filename,
            'bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ];

        $this->guard->run(
            PurchaseRequestLocks::import(Auth::id()),
            fn () => $this->purchaseRequests->importPurchaseRequests($contents, $filename),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'import', $exception, $file),
        );

        AuditLog::record(
            action: AuditAction::PurchaseRequestsImported,
            metadata: ['provider' => $provider, ...$file],
        );
    }
}
