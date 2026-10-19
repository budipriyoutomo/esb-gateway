<?php

namespace App\Imports\DocumentTemplates;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\ErrorCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

/**
 * The cells of an uploaded .xlsx file's first sheet, by row number (ADR-023).
 *
 * The file is untrusted input: anything openspout cannot read becomes one
 * message for the uploader, and the row count is bounded so a huge sheet is
 * refused rather than read into memory.
 */
class DocumentTemplateSheetReader
{
    public const MAX_ROWS = 5000;

    /**
     * @return array<int, list<mixed>>
     *
     * @throws InvalidDocumentTemplateSheet
     */
    public function read(string $path): array
    {
        $options = new Options;
        // Keeps empty rows in the count, so row numbers match what the
        // uploader sees in Excel.
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;

        $reader = new Reader($options);
        $rows = [];

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    if ($rowNumber > self::MAX_ROWS) {
                        throw new InvalidDocumentTemplateSheet(sprintf('The sheet has more than %d rows.', self::MAX_ROWS));
                    }

                    $rows[$rowNumber] = array_values(array_map($this->valueOf(...), $row->getCells()));
                }

                break;
            }
        } catch (InvalidDocumentTemplateSheet $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new InvalidDocumentTemplateSheet('The file could not be read as an .xlsx workbook.');
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /**
     * What the cell shows in Excel. A formula reads as the result Excel saved
     * with it -- the business's sheet fills Product Code with a VLOOKUP into
     * another workbook, which could never be recalculated here. A formula
     * whose result was an error (#N/A) reads as empty.
     */
    private function valueOf(Cell $cell): mixed
    {
        return match (true) {
            $cell instanceof FormulaCell => $cell->getComputedValue(),
            $cell instanceof ErrorCell => null,
            default => $cell->getValue(),
        };
    }
}
