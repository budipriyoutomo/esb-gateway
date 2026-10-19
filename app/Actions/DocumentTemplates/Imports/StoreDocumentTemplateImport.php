<?php

namespace App\Actions\DocumentTemplates\Imports;

use App\Audit\AuditAction;
use App\Imports\DocumentTemplates\DocumentTemplateSheetParser;
use App\Imports\DocumentTemplates\DocumentTemplateSheetReader;
use App\Imports\DocumentTemplates\InvalidDocumentTemplateSheet;
use App\Imports\DocumentTemplates\ParsedTemplate;
use App\Models\AuditLog;
use App\Models\DocumentTemplateImport;
use App\Models\DocumentTemplateImportEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Read an uploaded document template sheet into the gateway's database
 * (ADR-023). The ESB is not called: a template the sheet already gets wrong is
 * stored as invalid, every other one as pending, for validate to check.
 */
class StoreDocumentTemplateImport
{
    public function __construct(
        private readonly DocumentTemplateSheetReader $reader,
        private readonly DocumentTemplateSheetParser $parser,
    ) {}

    /**
     * @throws InvalidDocumentTemplateSheet when the file is not a usable sheet
     */
    public function handle(UploadedFile $file, ?int $uploadedBy): DocumentTemplateImport
    {
        $path = (string) $file->getRealPath();
        $sheet = $this->parser->parse($this->reader->read($path));

        return DB::transaction(function () use ($file, $path, $sheet, $uploadedBy): DocumentTemplateImport {
            $import = DocumentTemplateImport::query()->create([
                'uploaded_by' => $uploadedBy,
                'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_sha256' => (string) hash_file('sha256', $path),
                'problems' => $sheet->problems,
            ]);

            foreach ($sheet->templates as $index => $template) {
                $this->storeTemplate($import, $index + 1, $template);
            }

            AuditLog::record(
                action: AuditAction::DocumentTemplateImportUploaded,
                auditable: $import,
                actorId: $uploadedBy,
                metadata: [
                    'import_id' => $import->id,
                    'filename' => $import->filename,
                    'sha256' => $import->file_sha256,
                    'templates' => count($sheet->templates),
                    'lines' => $sheet->lineCount(),
                    'invalid' => count(array_filter($sheet->templates, static fn (ParsedTemplate $template): bool => $template->problems !== [])),
                ],
            );

            return $import;
        });
    }

    private function storeTemplate(DocumentTemplateImport $import, int $position, ParsedTemplate $template): void
    {
        // Values longer than their column are already a problem on an invalid
        // template; they are clipped only so the record can be stored.
        $entry = $import->entries()->create([
            'position' => $position,
            'name' => mb_substr($template->name, 0, 255),
            'type' => $template->type?->value,
            'branch_codes' => $template->branchCodes,
            'status' => $template->problems === []
                ? DocumentTemplateImportEntry::STATUS_PENDING
                : DocumentTemplateImportEntry::STATUS_INVALID,
            'problems' => $template->problems,
        ]);

        foreach ($template->lines as $line) {
            $entry->lines()->create([
                'row_number' => $line->rowNumber,
                'product_code' => mb_substr($line->productCode, 0, 100),
                'product_name' => mb_substr($line->productName, 0, 255),
                'unit' => mb_substr($line->unit, 0, 100),
                'category' => mb_substr($line->category, 0, 255),
            ]);
        }
    }
}
