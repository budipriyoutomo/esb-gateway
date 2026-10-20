<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * The uploaded "files" of a request, ready to forward to the ERP as
 * attachments ([ESB-032], [ESB-036]): their contents, with names reduced to
 * something safe to put in a multipart header and to audit.
 *
 * Use from a FormRequest that validates "files" as an array of files.
 */
trait ForwardsAttachments
{
    /**
     * @return list<array{contents: string, filename: string}>
     */
    public function attachments(): array
    {
        /** @var list<UploadedFile> $files */
        $files = $this->file('files');

        return array_map(static function (UploadedFile $file): array {
            $extension = strtolower($file->getClientOriginalExtension());
            $stem = pathinfo(basename(str_replace('\\', '/', $file->getClientOriginalName())), PATHINFO_FILENAME);
            $stem = mb_substr(trim((string) preg_replace('/[^A-Za-z0-9 ._-]+/', '', $stem), ' .'), 0, 100);

            return [
                'contents' => (string) file_get_contents($file->getRealPath()),
                'filename' => ($stem === '' ? 'attachment' : $stem).'.'.$extension,
            ];
        }, $files);
    }
}
