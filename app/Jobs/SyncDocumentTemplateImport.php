<?php

namespace App\Jobs;

use App\Actions\DocumentTemplates\Imports\SyncDocumentTemplateImport as Sync;
use App\Models\DocumentTemplateImport;
use App\Models\DocumentTemplateImportEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Send a document template import's ready templates to the ERP in the
 * background (ADR-023), on behalf of the person who asked.
 */
class SyncDocumentTemplateImport implements ShouldQueue
{
    use Queueable;

    /** Creates are not idempotent: never repeated by the queue. */
    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public readonly int $importId,
        public readonly ?int $actorId,
    ) {}

    public function handle(Sync $sync): void
    {
        $import = DocumentTemplateImport::query()->find($this->importId);

        if ($import === null || $import->status !== DocumentTemplateImport::STATUS_SYNCING) {
            return;
        }

        $sync->handle($import, $this->actorId);
    }

    /**
     * A template left mid-send may or may not be in the ERP: it is marked
     * unconfirmed, for validate to settle.
     */
    public function failed(?Throwable $exception): void
    {
        DocumentTemplateImportEntry::query()
            ->where('document_template_import_id', $this->importId)
            ->where('status', DocumentTemplateImportEntry::STATUS_SYNCING)
            ->update([
                'status' => DocumentTemplateImportEntry::STATUS_UNCONFIRMED,
                'problems' => json_encode(['The sync stopped while sending this template. It may or may not have been created -- validate again to find out.']),
            ]);

        DocumentTemplateImport::query()
            ->whereKey($this->importId)
            ->where('status', DocumentTemplateImport::STATUS_SYNCING)
            ->update([
                'status' => DocumentTemplateImport::STATUS_IDLE,
                'last_error' => 'Sync stopped unexpectedly. Validate again before syncing the rest.',
            ]);
    }
}
