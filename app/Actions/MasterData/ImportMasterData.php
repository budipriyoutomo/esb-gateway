<?php

namespace App\Actions\MasterData;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\MasterDataImport;
use App\Integrations\Contracts\MasterDataImportSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Hand the ERP a master data spreadsheet, on behalf of a person ([ESB-032]).
 *
 * As with purchase requests, the gateway neither parses nor stores the file:
 * the layouts are the ERP's own templates. The audit identifies it by name,
 * size and SHA-256. One import of each kind per person at a time; importing
 * the same sheet twice may create every row twice.
 */
class ImportMasterData
{
    public function __construct(
        private readonly MasterDataImportSource $imports,
        private readonly GuardedEsbWrite $guard,
    ) {}

    public static function lock(MasterDataImport $kind, int|string|null $userId): string
    {
        return 'master-data-import:'.$kind->value.':'.($userId ?? 'guest');
    }

    /**
     * @return array<string, int> What the ERP says it saved; empty when it queued the file.
     *
     * @throws WriteAlreadyInFlight when this person's previous import of this kind is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(MasterDataImport $kind, string $contents, string $filename): array
    {
        $provider = $this->imports->provider();
        $file = [
            'kind' => $kind->value,
            'filename' => $filename,
            'bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ];

        $saved = $this->guard->run(
            self::lock($kind, Auth::id()),
            fn (): array => $this->imports->import($kind, $contents, $filename),
            function (EsbException $exception) use ($provider, $file): void {
                AuditLog::record(
                    action: AuditAction::MasterDataImportUnconfirmed,
                    metadata: [
                        'provider' => $provider,
                        ...$file,
                        'failure' => $exception->kind(),
                        'correlation_id' => $exception->context->correlationId,
                    ],
                );
            },
        );

        AuditLog::record(
            action: AuditAction::MasterDataImported,
            metadata: ['provider' => $provider, ...$file, ...($saved === [] ? [] : ['saved' => $saved])],
        );

        return $saved;
    }
}
