<?php

namespace App\Jobs;

use App\Actions\DocumentTemplates\Imports\ValidateDocumentTemplateImport as Validate;
use App\Models\DocumentTemplateImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Check a document template import against the ERP in the background
 * (ADR-023): one ESB read per product code and per template name is too many
 * for a web request.
 */
class ValidateDocumentTemplateImport implements ShouldQueue
{
    use Queueable;

    /** Reads only, but a person starts it again from the page; no silent repeats. */
    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public readonly int $importId) {}

    public function handle(Validate $validate): void
    {
        $import = DocumentTemplateImport::query()->find($this->importId);

        if ($import === null || $import->status !== DocumentTemplateImport::STATUS_VALIDATING) {
            return;
        }

        $validate->handle($import);
    }

    public function failed(?Throwable $exception): void
    {
        DocumentTemplateImport::query()
            ->whereKey($this->importId)
            ->where('status', DocumentTemplateImport::STATUS_VALIDATING)
            ->update([
                'status' => DocumentTemplateImport::STATUS_IDLE,
                'last_error' => 'Validation stopped unexpectedly. Validate again.',
            ]);
    }
}
