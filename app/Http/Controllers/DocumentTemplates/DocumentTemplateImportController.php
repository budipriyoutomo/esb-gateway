<?php

namespace App\Http\Controllers\DocumentTemplates;

use App\Actions\DocumentTemplates\Imports\StoreDocumentTemplateImport;
use App\Audit\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentTemplates\UploadDocumentTemplateImportRequest;
use App\Imports\DocumentTemplates\DocumentTemplateSheetTemplate;
use App\Imports\DocumentTemplates\InvalidDocumentTemplateSheet;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Jobs\SyncDocumentTemplateImport;
use App\Jobs\ValidateDocumentTemplateImport;
use App\Models\AuditLog;
use App\Models\DocumentTemplateImport;
use App\Models\DocumentTemplateImportEntry;
use App\Models\DocumentTemplateImportLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import document templates from a spreadsheet (ADR-023): upload into the
 * gateway's database, validate against the ERP, then sync the ready ones.
 *
 * Validate and sync run on the queue. An import is claimed before its job is
 * queued, so a second click while one runs is refused rather than queued.
 */
class DocumentTemplateImportController extends Controller
{
    private const LIST_LIMIT = 50;

    public function index(): Response
    {
        $imports = DocumentTemplateImport::query()
            ->with('uploader:id,name')
            ->withCount('entries')
            ->latest('id')
            ->limit(self::LIST_LIMIT)
            ->get();

        $counts = DocumentTemplateImportEntry::query()
            ->whereIn('document_template_import_id', $imports->modelKeys())
            ->selectRaw('document_template_import_id, status, count(*) as aggregate')
            ->groupBy('document_template_import_id', 'status')
            ->get()
            ->groupBy('document_template_import_id');

        return Inertia::render('document-template-imports/index', [
            'imports' => $imports->map(fn (DocumentTemplateImport $import): array => [
                'id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'uploadedBy' => $import->uploader?->name,
                'createdAt' => $import->created_at?->toIso8601String(),
                'templates' => $import->entries_count,
                'counts' => ($counts->get($import->id) ?? collect())
                    ->mapWithKeys(fn ($row): array => [$row->status => (int) $row->aggregate])
                    ->all(),
            ])->values()->all(),
        ]);
    }

    /**
     * The blank spreadsheet to fill in. The ERP's branch codes are added as of
     * now; if the ERP cannot be asked, the template still downloads without
     * them.
     */
    public function template(DocumentTemplateSheetTemplate $template, PurchaseRequestSource $branches): BinaryFileResponse
    {
        try {
            $branchList = $branches->listBranches();
        } catch (EsbException) {
            $branchList = null;
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'dt-template');
        $template->write($path, $branchList);

        return response()
            ->download($path, DocumentTemplateSheetTemplate::FILENAME, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    public function store(UploadDocumentTemplateImportRequest $request, StoreDocumentTemplateImport $store): RedirectResponse
    {
        try {
            $import = $store->handle($request->upload(), $this->userId());
        } catch (InvalidDocumentTemplateSheet $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $this->toast('success', 'The file was read and stored. Validate it against the ERP next.');

        return redirect()->route('document-template-imports.show', $import);
    }

    public function show(DocumentTemplateImport $import): Response
    {
        $import->load(['uploader:id,name', 'entries.lines']);

        $counts = array_fill_keys(DocumentTemplateImportEntry::STATUSES, 0);

        foreach ($import->entries as $entry) {
            $counts[$entry->status]++;
        }

        return Inertia::render('document-template-imports/show', [
            'import' => [
                'id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'problems' => $import->problems,
                'lastError' => $import->last_error,
                'uploadedBy' => $import->uploader?->name,
                'createdAt' => $import->created_at?->toIso8601String(),
                'validatedAt' => $import->validated_at?->toIso8601String(),
                'syncedAt' => $import->synced_at?->toIso8601String(),
            ],
            'busy' => $import->isBusy(),
            'counts' => $counts,
            'entries' => $import->entries->map(fn (DocumentTemplateImportEntry $entry): array => [
                'id' => $entry->id,
                'position' => $entry->position,
                'name' => $entry->name,
                'typeName' => $entry->documentTemplateType()?->label(),
                'branchCodes' => $entry->branch_codes,
                'status' => $entry->status,
                'problems' => $entry->problems,
                'esbTemplateId' => $entry->esb_template_id,
                'lines' => $entry->lines->map(fn (DocumentTemplateImportLine $line): array => [
                    'rowNumber' => $line->row_number,
                    'productCode' => $line->product_code,
                    'productName' => $line->product_name,
                    'unit' => $line->unit,
                    'category' => $line->category,
                    'productDetailId' => $line->product_detail_id,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function startValidation(DocumentTemplateImport $import): RedirectResponse
    {
        if (! $import->claim(DocumentTemplateImport::STATUS_VALIDATING)) {
            return $this->busy($import);
        }

        AuditLog::record(
            action: AuditAction::DocumentTemplateImportValidationRequested,
            auditable: $import,
            metadata: ['import_id' => $import->id],
        );

        ValidateDocumentTemplateImport::dispatch($import->id);

        $this->toast('success', 'Validation started. This page updates as it runs.');

        return redirect()->route('document-template-imports.show', $import);
    }

    public function startSync(DocumentTemplateImport $import): RedirectResponse
    {
        $ready = $import->entries()->where('status', DocumentTemplateImportEntry::STATUS_READY)->count();

        if ($ready === 0) {
            $this->toast('warning', 'No template is ready to send. Validate the import first.');

            return redirect()->route('document-template-imports.show', $import);
        }

        if (! $import->claim(DocumentTemplateImport::STATUS_SYNCING)) {
            return $this->busy($import);
        }

        AuditLog::record(
            action: AuditAction::DocumentTemplateImportSyncRequested,
            auditable: $import,
            metadata: ['import_id' => $import->id, 'ready' => $ready],
        );

        SyncDocumentTemplateImport::dispatch($import->id, $this->userId());

        $this->toast('success', sprintf('Sending %d template(s) to the ERP. This page updates as it runs.', $ready));

        return redirect()->route('document-template-imports.show', $import);
    }

    /**
     * Users have integer keys; the guard's id() is typed wider.
     */
    private function userId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }

    private function busy(DocumentTemplateImport $import): RedirectResponse
    {
        $this->toast('warning', 'This import is already being validated or synced. Wait for it to finish.');

        return redirect()->route('document-template-imports.show', $import);
    }
}
