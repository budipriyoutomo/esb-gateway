<?php

namespace App\Exports;

use App\Domain\Canonical\Pagination;
use Closure;

/**
 * A list as a CSV file, built by the gateway (ADR-031).
 *
 * Every page is read before anything is written, so a failed read is an
 * error, never a truncated file.
 */
final class CsvExport
{
    /** The most rows one export holds. */
    public const MAX_ROWS = 5000;

    /** The page size asked of the provider: the largest every list accepts. */
    public const PAGE_SIZE = 100;

    private const BOM = "\u{FEFF}";

    /** A text cell starting with one of these runs as a formula in a spreadsheet. */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @template TRow
     *
     * @param  Closure(int): array{list<TRow>, Pagination}  $readPage  One page, 1-based.
     * @return list<TRow>
     *
     * @throws TooManyRowsToExport when the list holds more than MAX_ROWS
     */
    public function readAll(Closure $readPage): array
    {
        $rows = [];
        $page = 1;

        do {
            [$pageRows, $pagination] = $readPage($page);

            if ($pagination->count > self::MAX_ROWS) {
                throw new TooManyRowsToExport($pagination->count);
            }

            array_push($rows, ...$pageRows);
            $page++;
            // Stop on the row count, not on the provider's page number: a
            // provider that echoes the wrong page must not loop.
        } while ($pageRows !== [] && count($rows) < $pagination->count);

        return $rows;
    }

    /**
     * @template TRow
     *
     * @param  array<string, Closure(TRow): (string|int|float|bool|null)>  $columns  Header => cell.
     * @param  iterable<TRow>  $rows
     */
    public function toCsv(array $columns, iterable $rows): string
    {
        $csv = self::BOM.$this->line(array_keys($columns));

        foreach ($rows as $row) {
            $csv .= $this->line(array_map(static fn (Closure $cell) => $cell($row), array_values($columns)));
        }

        return $csv;
    }

    /**
     * @param  list<string|int|float|bool|null>  $cells
     */
    private function line(array $cells): string
    {
        return implode(',', array_map($this->cell(...), $cells))."\r\n";
    }

    private function cell(string|int|float|bool|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true)) {
            $value = "'".$value;
        }

        return strpbrk($value, ",\"\r\n") === false
            ? $value
            : '"'.str_replace('"', '""', $value).'"';
    }
}
