<?php

namespace App\Imports\DocumentTemplates;

use App\Domain\Canonical\DocumentTemplateRules;
use App\Domain\Canonical\DocumentTemplateType;
use DateTimeInterface;

/**
 * Reads the rows of a document template sheet into templates (ADR-023).
 *
 * The layout is the business's "Document Template ESB_Supplier.xlsx": one row
 * per product, templates told apart by name. Columns are found by their
 * header text, so their order may change. Nothing here calls the ESB; what
 * only the ERP can say (does this branch or product exist?) is checked later.
 *
 * Owner's decisions (2026-10-15): a template's branches are every row's
 * branches merged, since the ESB holds branches per template; and the
 * category becomes each line's name.
 */
class DocumentTemplateSheetParser
{
    /**
     * Column key => header text, in the order a missing column is reported
     * and the downloadable template lays them out.
     */
    public const COLUMNS = [
        'name' => 'Document Template Name',
        'type' => 'Document Template Type',
        'branches' => 'Branch Aktif',
        'category' => 'Kategori',
        'productName' => 'Product Name',
        'productCode' => 'Product Code',
        'unit' => 'Unit',
    ];

    /**
     * @param  iterable<int, list<mixed>>  $rows  Row number => cell values.
     *
     * @throws InvalidDocumentTemplateSheet when the sheet has no usable header or no template rows
     */
    public function parse(iterable $rows): ParsedSheet
    {
        $columns = null;
        $groups = [];
        $problems = [];

        foreach ($rows as $rowNumber => $cells) {
            if ($columns === null) {
                $columns = $this->headerColumns($cells);

                continue;
            }

            $values = [];

            foreach ($columns as $key => $index) {
                $values[$key] = $this->text($cells[$index] ?? null);
            }

            if (implode('', $values) === '') {
                continue;
            }

            if ($values['name'] === '') {
                $problems[] = $values['productCode'] !== ''
                    ? sprintf('Row %d: product %s has no template name.', $rowNumber, $values['productCode'])
                    : sprintf('Row %d: has product data but no template name.', $rowNumber);

                continue;
            }

            $groups[$values['name']][] = ['row' => $rowNumber, ...$values];
        }

        if ($columns === null) {
            throw new InvalidDocumentTemplateSheet(sprintf('No header row with "%s" was found.', self::COLUMNS['name']));
        }

        if ($groups === [] && $problems === []) {
            throw new InvalidDocumentTemplateSheet('The sheet has no template rows.');
        }

        return new ParsedSheet(
            templates: array_map($this->template(...), array_keys($groups), array_values($groups)),
            problems: $problems,
        );
    }

    /**
     * The column index of every expected header, or null when this row is
     * not the header row.
     *
     * @param  list<mixed>  $cells
     * @return array<string, int>|null
     *
     * @throws InvalidDocumentTemplateSheet when the header row lacks columns
     */
    private function headerColumns(array $cells): ?array
    {
        $headers = array_map(fn (mixed $cell): string => $this->normalize($this->text($cell)), $cells);

        if (! in_array($this->normalize(self::COLUMNS['name']), $headers, true)) {
            return null;
        }

        $columns = [];
        $missing = [];

        foreach (self::COLUMNS as $key => $label) {
            $index = array_search($this->normalize($label), $headers, true);

            if ($index === false) {
                $missing[] = $label;

                continue;
            }

            $columns[$key] = $index;
        }

        if ($missing !== []) {
            throw new InvalidDocumentTemplateSheet(sprintf('Missing columns: %s.', implode(', ', $missing)));
        }

        return $columns;
    }

    /**
     * @param  list<array<string, int|string>>  $rows
     */
    private function template(string $name, array $rows): ParsedTemplate
    {
        $problems = [];
        $type = null;
        $typeRow = null;
        $branchCodes = [];
        $lines = [];
        $seen = [];

        if (DocumentTemplateRules::hasEmoji($name)) {
            $problems[] = 'The ERP does not accept emoji in the template name.';
        }

        if (mb_strlen($name) > DocumentTemplateRules::MAX_NAME_LENGTH) {
            $problems[] = sprintf('The template name is longer than %d characters.', DocumentTemplateRules::MAX_NAME_LENGTH);
        }

        foreach ($rows as $row) {
            $rowNumber = (int) $row['row'];
            $typeText = (string) $row['type'];

            if ($typeText === '') {
                $problems[] = sprintf('Row %d: template type is empty.', $rowNumber);
            } elseif (($rowType = DocumentTemplateType::fromLabel($typeText)) === null) {
                $problems[] = sprintf(
                    'Row %d: unknown template type "%s" (expected %s).',
                    $rowNumber,
                    $typeText,
                    $this->typeLabels(),
                );
            } elseif ($type === null) {
                $type = $rowType;
                $typeRow = $rowNumber;
            } elseif ($rowType !== $type) {
                $problems[] = sprintf('Row %d: type "%s" differs from "%s" on row %d.', $rowNumber, $rowType->label(), $type->label(), $typeRow);
            }

            foreach (explode(',', (string) $row['branches']) as $code) {
                $code = strtoupper(trim($code));

                if ($code !== '' && ! in_array($code, $branchCodes, true)) {
                    $branchCodes[] = $code;
                }
            }

            $code = (string) $row['productCode'];
            $unit = (string) $row['unit'];
            $category = (string) $row['category'];

            if ($code === '') {
                $problems[] = sprintf('Row %d: product code is empty.', $rowNumber);
            }

            if ($unit === '') {
                $problems[] = sprintf('Row %d: unit is empty.', $rowNumber);
            }

            if ($category === '') {
                $problems[] = sprintf('Row %d: category (used as the line name) is empty.', $rowNumber);
            } elseif (DocumentTemplateRules::hasEmoji($category)) {
                $problems[] = sprintf('Row %d: the ERP does not accept emoji in the category.', $rowNumber);
            } elseif (mb_strlen($category) > DocumentTemplateRules::MAX_NAME_LENGTH) {
                $problems[] = sprintf('Row %d: the category is longer than %d characters.', $rowNumber, DocumentTemplateRules::MAX_NAME_LENGTH);
            }

            if ($code !== '' && $unit !== '') {
                $key = strtoupper($code).'|'.$this->normalize($unit);

                if (isset($seen[$key])) {
                    $problems[] = sprintf('Row %d: product %s in %s is already on row %d.', $rowNumber, $code, $unit, $seen[$key]);
                } else {
                    $seen[$key] = $rowNumber;
                }
            }

            $lines[] = new ParsedLine(
                rowNumber: $rowNumber,
                productCode: $code,
                productName: (string) $row['productName'],
                unit: $unit,
                category: $category,
            );
        }

        if ($branchCodes === []) {
            $problems[] = 'No branch is listed for this template.';
        }

        return new ParsedTemplate(
            name: $name,
            type: $type,
            branchCodes: $branchCodes,
            lines: $lines,
            problems: $problems,
        );
    }

    private function typeLabels(): string
    {
        $labels = array_map(static fn (DocumentTemplateType $type): string => $type->label(), DocumentTemplateType::cases());
        $last = array_pop($labels);

        return implode(', ', $labels).', or '.$last;
    }

    /**
     * A cell as trimmed text. Whole numbers lose their ".0", so a product
     * code typed as a number reads as it looks.
     */
    private function text(mixed $value): string
    {
        return match (true) {
            is_string($value) => trim($value),
            is_int($value) => (string) $value,
            is_float($value) => floor($value) === $value ? (string) (int) $value : (string) $value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => '',
        };
    }

    /**
     * For comparing headers and units: lower case, single spaces.
     */
    private function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
    }
}
