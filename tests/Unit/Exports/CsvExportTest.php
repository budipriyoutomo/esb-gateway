<?php

namespace Tests\Unit\Exports;

use App\Domain\Canonical\Pagination;
use App\Exports\CsvExport;
use App\Exports\TooManyRowsToExport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The gateway's CSV list export (ADR-031).
 */
class CsvExportTest extends TestCase
{
    private const BOM = "\u{FEFF}";

    /**
     * A source of $count rows, as pages of $limit, recording each page read.
     *
     * @param  list<int>  $reads
     * @return \Closure(int): array{list<int>, Pagination}
     */
    private function source(int $count, int $limit, array &$reads): \Closure
    {
        return function (int $page) use ($count, $limit, &$reads): array {
            $reads[] = $page;
            $first = ($page - 1) * $limit;
            $rows = $first >= $count ? [] : range($first + 1, min($count, $first + $limit));

            return [$rows, new Pagination(page: $page, limit: $limit, count: $count)];
        };
    }

    #[Test]
    public function every_page_is_read_in_order(): void
    {
        $reads = [];

        $rows = (new CsvExport)->readAll($this->source(250, 100, $reads));

        $this->assertSame([1, 2, 3], $reads);
        $this->assertSame(range(1, 250), $rows);
    }

    #[Test]
    public function a_provider_echoing_the_wrong_page_does_not_loop(): void
    {
        $reads = [];

        $rows = (new CsvExport)->readAll(function (int $page) use (&$reads): array {
            $reads[] = $page;

            return [[$page], new Pagination(page: 1, limit: 1, count: 3)];
        });

        $this->assertSame([1, 2, 3], $reads);
        $this->assertSame([1, 2, 3], $rows);
    }

    #[Test]
    public function an_empty_list_is_one_read(): void
    {
        $reads = [];

        $this->assertSame([], (new CsvExport)->readAll($this->source(0, 100, $reads)));
        $this->assertSame([1], $reads);
    }

    #[Test]
    public function too_many_rows_are_refused_after_the_first_page(): void
    {
        $reads = [];

        try {
            (new CsvExport)->readAll($this->source(CsvExport::MAX_ROWS + 1, 100, $reads));
            $this->fail('Expected TooManyRowsToExport.');
        } catch (TooManyRowsToExport $exception) {
            $this->assertSame(CsvExport::MAX_ROWS + 1, $exception->count);
        }

        // The count is known from the first page; nothing more is read.
        $this->assertSame([1], $reads);
    }

    #[Test]
    public function a_failed_read_is_not_swallowed(): void
    {
        $this->expectException(RuntimeException::class);

        (new CsvExport)->readAll(function (int $page): array {
            if ($page === 2) {
                throw new RuntimeException('ESB down');
            }

            return [[1], new Pagination(page: $page, limit: 1, count: 3)];
        });
    }

    #[Test]
    public function the_csv_has_a_bom_a_header_and_one_line_per_row(): void
    {
        $csv = (new CsvExport)->toCsv(
            ['Code' => fn (array $row) => $row['code'], 'Amount' => fn (array $row) => $row['amount']],
            [['code' => 'A-1', 'amount' => 1500.5], ['code' => 'B, "2"', 'amount' => null]],
        );

        $this->assertSame(
            self::BOM."Code,Amount\r\nA-1,1500.5\r\n\"B, \"\"2\"\"\",\r\n",
            $csv,
        );
    }

    #[Test]
    public function text_that_a_spreadsheet_would_run_as_a_formula_is_neutralised(): void
    {
        $csv = (new CsvExport)->toCsv(
            ['Value' => fn ($row) => $row],
            ['=HYPERLINK("x")', '+1', '-2', '@SUM(A1)', "\tx", -3, -4.5, true, false],
        );

        $lines = explode("\r\n", substr($csv, strlen(self::BOM)));

        $this->assertSame([
            'Value',
            '"\'=HYPERLINK(""x"")"',
            "'+1",
            "'-2",
            "'@SUM(A1)",
            "'\tx",
            // Numbers stay numbers, negative ones included.
            '-3',
            '-4.5',
            'Yes',
            'No',
            '',
        ], $lines);
    }
}
