<?php

namespace App\Http\Requests\DocumentTemplates;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use Closure;
use Illuminate\Http\UploadedFile;

/**
 * A document template spreadsheet to import (ADR-023).
 *
 * Checked by extension and by the zip signature every .xlsx starts with,
 * like the purchase request import: a browser's MIME guess for .xlsx varies.
 */
class UploadDocumentTemplateImportRequest extends BaseFormRequest
{
    public const MAX_KILOBYTES = 2048;

    private const XLSX_SIGNATURE = "PK\x03\x04";

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageDocumentTemplates);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'extensions:xlsx',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! $this->looksLikeXlsx($value)) {
                        $fail('The file must be an Excel workbook (.xlsx).');
                    }
                },
            ],
        ];
    }

    public function upload(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }

    private function looksLikeXlsx(UploadedFile $file): bool
    {
        $handle = @fopen((string) $file->getRealPath(), 'rb');

        if ($handle === false) {
            return false;
        }

        $start = (string) fread($handle, strlen(self::XLSX_SIGNATURE));
        fclose($handle);

        return $start === self::XLSX_SIGNATURE;
    }
}
