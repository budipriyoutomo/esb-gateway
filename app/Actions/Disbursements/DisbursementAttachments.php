<?php

namespace App\Actions\Disbursements;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Attach files to a disbursement ([ESB-040]), under the disbursement's
 * lock. The audit names each file with its size and SHA-256.
 */
class DisbursementAttachments
{
    public function __construct(
        private readonly DisbursementSource $disbursements,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws WriteAlreadyInFlight when another write on this disbursement is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function attach(string $number, array $files): void
    {
        $this->writes->transition(
            Disbursements::approvable(),
            $this->disbursements->provider(),
            $number,
            'attach',
            AuditAction::DisbursementAttachmentsUploaded,
            fn () => $this->disbursements->attachFiles($number, $files),
            ['files' => array_map(static fn (array $file): array => [
                'filename' => $file['filename'],
                'bytes' => strlen($file['contents']),
                'sha256' => hash('sha256', $file['contents']),
            ], $files)],
        );
    }
}
