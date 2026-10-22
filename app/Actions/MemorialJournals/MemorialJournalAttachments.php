<?php

namespace App\Actions\MemorialJournals;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Attach an image to a memorial journal ([ESB-046]), under the journal's
 * lock. The audit names the file with its size and SHA-256.
 */
class MemorialJournalAttachments
{
    public function __construct(
        private readonly MemorialJournalSource $journals,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this journal is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function attach(string $number, string $contents, string $filename): void
    {
        $this->writes->transition(
            MemorialJournals::approvable(),
            $this->journals->provider(),
            $number,
            'attach',
            AuditAction::MemorialJournalImageUploaded,
            fn () => $this->journals->attachImage($number, $contents, $filename),
            ['file' => [
                'filename' => $filename,
                'bytes' => strlen($contents),
                'sha256' => hash('sha256', $contents),
            ]],
        );
    }
}
