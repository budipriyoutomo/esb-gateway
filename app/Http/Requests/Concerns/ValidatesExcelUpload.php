<?php

namespace App\Http\Requests\Concerns;

use Closure;
use Illuminate\Http\UploadedFile;

/**
 * The checks that stop a pointless spreadsheet upload to the ERP: the size
 * limit, the extension, and the file's leading bytes (checked directly, not
 * through MIME sniffing, which reports xlsx as application/zip on some
 * libmagic versions). The gateway does not parse the sheet.
 *
 * For a FormRequest with a "file" field.
 */
trait ValidatesExcelUpload
{
    /**
     * @param  list<string>  $extensions  'xlsx' and / or 'xls'
     * @return list<mixed>
     */
    protected function excelFileRules(int $maxKilobytes, array $extensions): array
    {
        return [
            'required',
            'file',
            'max:'.$maxKilobytes,
            'extensions:'.implode(',', $extensions),
            function (string $attribute, mixed $value, Closure $fail) use ($extensions): void {
                if (! $value instanceof UploadedFile || ! $this->looksLikeExcel($value, $extensions)) {
                    $fail($this->excelTypeMessage($extensions));
                }
            },
        ];
    }

    /**
     * @param  list<string>  $extensions
     */
    protected function excelTypeMessage(array $extensions): string
    {
        return 'The file must be an Excel workbook ('.implode(' or ', array_map(static fn (string $e): string => '.'.$e, $extensions)).').';
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
    protected function safeExcelFilename(string $fallback): string
    {
        $file = $this->uploadedFile();
        $extension = strtolower($file->getClientOriginalExtension()) === 'xls' ? 'xls' : 'xlsx';

        $stem = pathinfo(basename(str_replace('\\', '/', $file->getClientOriginalName())), PATHINFO_FILENAME);
        $stem = trim((string) preg_replace('/[^A-Za-z0-9 ._-]+/', '', $stem), ' .');
        $stem = mb_substr($stem, 0, 100);

        return ($stem === '' ? $fallback : $stem).'.'.$extension;
    }

    private function uploadedFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }

    /**
     * Every xlsx is a zip archive; every legacy xls an OLE2 compound document.
     *
     * @param  list<string>  $extensions
     */
    private function looksLikeExcel(UploadedFile $file, array $extensions): bool
    {
        $handle = @fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 8);
        fclose($handle);

        return (in_array('xlsx', $extensions, true) && str_starts_with($head, "PK\x03\x04"))
            || (in_array('xls', $extensions, true) && $head === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1");
    }
}
