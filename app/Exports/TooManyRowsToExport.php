<?php

namespace App\Exports;

use RuntimeException;

/**
 * A list export would hold more rows than CsvExport::MAX_ROWS (ADR-031).
 */
final class TooManyRowsToExport extends RuntimeException
{
    public function __construct(public readonly int $count)
    {
        parent::__construct(sprintf('%d rows is more than an export holds (%d).', $count, CsvExport::MAX_ROWS));
    }
}
