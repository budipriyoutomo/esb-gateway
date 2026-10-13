<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use Closure;
use Illuminate\Http\UploadedFile;

/**
 * An Excel file of purchase requests to hand to the ERP.
 *
 * The vendor accepts xlsx or xls up to 2 MB. The gateway does not parse the
 * sheet (its layout is undocumented), so the checks here are the ones that
 * stop a pointless upload: the size limit, the extension, and the file's
 * leading bytes. The bytes are checked directly rather than through the
 * server's MIME sniffing, which reports xlsx as application/zip on some
 * libmagic versions.
 */
class ImportPurchaseRequestsRequest extends BaseFormRequest
{
    /** The vendor's documented limit. */
    public const MAX_KILOBYTES = 2048;

    /** Every xlsx is a zip archive. */
    private const XLSX_SIGNATURE = "PK\x03\x04";

    /** Every legacy xls is an OLE2 compound document. */
    private const XLS_SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManagePurchaseRequests);
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
                'extensions:xlsx,xls',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! $this->looksLikeExcel($value)) {
                        $fail('The file must be an Excel workbook (.xlsx or .xls).');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'The ERP accepts files of at most 2 MB.',
            'file.extensions' => 'The file must be an Excel workbook (.xlsx or .xls).',
        ];
    }

    public function contents(): string
    {
        return (string) file_get_contents($this->uploadedFile()->getRealPath());
    }

    /**
     * The client's filename reduced to something safe to forward in a
     * multipart header and to show in an audit row: a basename of plain
     * characters, keeping the extension the ESB may rely on.
     */
    public function safeFilename(): string
    {
        $file = $this->uploadedFile();
        $extension = strtolower($file->getClientOriginalExtension()) === 'xls' ? 'xls' : 'xlsx';

        $stem = pathinfo(basename(str_replace('\\', '/', $file->getClientOriginalName())), PATHINFO_FILENAME);
        $stem = trim((string) preg_replace('/[^A-Za-z0-9 ._-]+/', '', $stem), ' .');
        $stem = mb_substr($stem, 0, 100);

        return ($stem === '' ? 'purchase-requests' : $stem).'.'.$extension;
    }

    private function uploadedFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }

    private function looksLikeExcel(UploadedFile $file): bool
    {
        $handle = @fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 8);
        fclose($handle);

        return str_starts_with($head, self::XLSX_SIGNATURE) || $head === self::XLS_SIGNATURE;
    }
}
