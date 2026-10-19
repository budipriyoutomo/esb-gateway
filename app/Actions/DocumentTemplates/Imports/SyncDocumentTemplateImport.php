<?php

namespace App\Actions\DocumentTemplates\Imports;

use App\Actions\DocumentTemplates\CreateDocumentTemplate;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\DocumentTemplateDraft;
use App\Domain\Canonical\DocumentTemplateDraftLine;
use App\Domain\Canonical\DocumentTemplateType;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\DocumentTemplateImport;
use App\Models\DocumentTemplateImportEntry;
use App\Models\DocumentTemplateImportLine;

/**
 * Send an import's ready templates to the ERP, one at a time, in file order
 * (ADR-023).
 *
 * Each goes through CreateDocumentTemplate -- the same lock, single attempt,
 * and audit trail as the create form -- on behalf of the person who asked
 * for the sync. Right before each create the name is checked again, so a
 * template someone made in the ERP since validation is not made twice.
 *
 * - accepted: synced, with the ERP's id when it returned one;
 * - refused: failed, with the ERP's reasons; the next template still goes;
 * - no definite answer: unconfirmed, and the run stops -- the ESB may be
 *   down, and validating again finds out whether it was created.
 *
 * The caller has claimed the import (status syncing); it is released here.
 */
class SyncDocumentTemplateImport
{
    public function __construct(
        private readonly CreateDocumentTemplate $create,
        private readonly ErpTemplateNames $names,
    ) {}

    public function handle(DocumentTemplateImport $import, ?int $actorId): void
    {
        $stoppedBecause = null;

        $entries = $import->entries()
            ->where('status', DocumentTemplateImportEntry::STATUS_READY)
            ->with('lines')
            ->get();

        foreach ($entries as $entry) {
            if (! $this->claim($entry)) {
                continue;
            }

            $stoppedBecause = $this->send($import, $entry, $actorId);

            if ($stoppedBecause !== null) {
                break;
            }
        }

        $import->release(array_filter([
            'synced_at' => now(),
            'last_error' => $stoppedBecause,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return string|null Why the run must stop, if it must.
     */
    private function send(DocumentTemplateImport $import, DocumentTemplateImportEntry $entry, ?int $actorId): ?string
    {
        try {
            $existing = $this->names->find($entry->name);
        } catch (EsbException $exception) {
            $entry->update(['status' => DocumentTemplateImportEntry::STATUS_READY]);

            // safeMessage() already ends with the reference.
            return 'Sync stopped: '.$exception->safeMessage();
        }

        if ($existing !== null) {
            $entry->update([
                'status' => DocumentTemplateImportEntry::STATUS_BLOCKED,
                'problems' => [ErpTemplateNames::takenMessage($existing)],
            ]);

            return null;
        }

        try {
            $id = $this->create->handle(
                $this->draftOf($entry),
                $actorId,
                ['import_id' => $import->id, 'import_entry_id' => $entry->id],
            );
        } catch (WriteAlreadyInFlight) {
            $entry->update(['status' => DocumentTemplateImportEntry::STATUS_READY]);

            return 'Sync stopped: another document template create by the same person is still running. Sync again in a moment.';
        } catch (EsbException $exception) {
            $reference = $exception->context->correlationId;

            if ($exception->mayHaveBeenApplied()) {
                $entry->update([
                    'status' => DocumentTemplateImportEntry::STATUS_UNCONFIRMED,
                    'correlation_id' => $reference,
                    'problems' => [sprintf(
                        'The ERP did not confirm this template. It may or may not have been created -- validate again to find out. (reference: %s)',
                        $reference,
                    )],
                ]);

                return sprintf('Sync stopped: the ERP did not confirm "%s". Validate again before syncing the rest. (reference: %s)', $entry->name, $reference);
            }

            $entry->update([
                'status' => DocumentTemplateImportEntry::STATUS_FAILED,
                'correlation_id' => $reference,
                'problems' => $this->refusalReasons($exception),
            ]);

            return null;
        }

        $entry->update([
            'status' => DocumentTemplateImportEntry::STATUS_SYNCED,
            'esb_template_id' => $id,
            'correlation_id' => null,
            'problems' => [],
            'synced_at' => now(),
        ]);

        return null;
    }

    /**
     * Only one run may send a template: ready -> syncing, or skip.
     */
    private function claim(DocumentTemplateImportEntry $entry): bool
    {
        $claimed = DocumentTemplateImportEntry::query()
            ->whereKey($entry->getKey())
            ->where('status', DocumentTemplateImportEntry::STATUS_READY)
            ->update(['status' => DocumentTemplateImportEntry::STATUS_SYNCING, 'updated_at' => now()]) === 1;

        if ($claimed) {
            $entry->status = DocumentTemplateImportEntry::STATUS_SYNCING;
        }

        return $claimed;
    }

    /**
     * Validation resolved every id before marking the template ready.
     */
    private function draftOf(DocumentTemplateImportEntry $entry): DocumentTemplateDraft
    {
        return new DocumentTemplateDraft(
            name: $entry->name,
            type: DocumentTemplateType::from((int) $entry->type),
            active: true,
            branchIds: $entry->branch_ids ?? [],
            lines: array_values($entry->lines->map(static fn (DocumentTemplateImportLine $line): DocumentTemplateDraftLine => new DocumentTemplateDraftLine(
                productDetailId: (int) $line->product_detail_id,
                name: $line->category,
            ))->all()),
        );
    }

    /**
     * @return list<string>
     */
    private function refusalReasons(EsbException $exception): array
    {
        $reasons = [];

        foreach ($exception->fieldErrors as $fieldError) {
            $reasons[] = 'The ERP refused it: '.$fieldError->message;
        }

        return $reasons === [] ? ['The ERP refused it: '.$exception->safeMessage()] : array_values(array_unique($reasons));
    }
}
