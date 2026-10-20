<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\MasterDataImport;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Hands master data spreadsheets to a provider ([ESB-032]).
 */
interface MasterDataImportSource
{
    public function provider(): string;

    /**
     * Send the file. One attempt. Returns what the provider says it saved,
     * e.g. ['accounts' => 12, 'mappings' => 3]; empty when it only queued it.
     *
     * @return array<string, int>
     *
     * @throws EsbException
     */
    public function import(MasterDataImport $kind, string $contents, string $filename): array;
}
