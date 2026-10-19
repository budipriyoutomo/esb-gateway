<?php

namespace Tests\Unit\Imports;

use App\Domain\Canonical\DocumentTemplateType;
use App\Imports\DocumentTemplates\DocumentTemplateSheetParser;
use App\Imports\DocumentTemplates\InvalidDocumentTemplateSheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The document template sheet, read into templates and lines ([ESB-016],
 * ADR-023). Pure: no ESB, no database.
 *
 * The layout is the business's "Document Template ESB_Supplier.xlsx": a title
 * row, a header row, then one row per product, templates told apart by name.
 */
class DocumentTemplateSheetParserTest extends TestCase
{
    private const HEADER = ['No', 'Document Template Name', 'Document Template Type ', 'Branch Aktif ', 'Kategori', 'Product Name', 'Product Code', 'Unit'];

    private const ALL = 'STHO, STTSM, STPVJ';

    /**
     * @param  list<list<mixed>>  $dataRows
     * @return array<int, list<mixed>>
     */
    private function sheet(array $dataRows): array
    {
        $rows = [1 => [null, null, null, null, null, null, null, null], 2 => self::HEADER];

        foreach ($dataRows as $index => $row) {
            $rows[$index + 3] = $row;
        }

        return $rows;
    }

    #[Test]
    public function rows_are_grouped_into_templates_by_name_in_file_order(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'Template PR Olih', 'Purchase Request', 'STTSM, STPVJ', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            [null, null, null, null, null, null, null, null],
            [2, 'Template BERRI', 'Purchase Request', self::ALL, 'BEV', 'FG Berri Orange', 'BC0002', 'JRC @ 5 LT'],
            [3, 'Template BERRI', 'Purchase Request', self::ALL, 'BEV', 'Juice 2 LT', 'BC0004', 'BTL @ 2 LT'],
        ]));

        $this->assertSame([], $parsed->problems);
        $this->assertCount(2, $parsed->templates);

        $olih = $parsed->templates[0];
        $this->assertSame('Template PR Olih', $olih->name);
        $this->assertSame(DocumentTemplateType::PurchaseRequest, $olih->type);
        $this->assertSame(['STTSM', 'STPVJ'], $olih->branchCodes);
        $this->assertSame([], $olih->problems);
        $this->assertCount(1, $olih->lines);
        $this->assertSame(3, $olih->lines[0]->rowNumber);
        $this->assertSame('EC0080', $olih->lines[0]->productCode);
        $this->assertSame('Pakcoy', $olih->lines[0]->productName);
        $this->assertSame('KG', $olih->lines[0]->unit);
        $this->assertSame('VEG', $olih->lines[0]->category);

        $this->assertSame('Template BERRI', $parsed->templates[1]->name);
        $this->assertCount(2, $parsed->templates[1]->lines);
        $this->assertSame([5, 6], array_map(fn ($line) => $line->rowNumber, $parsed->templates[1]->lines));
    }

    #[Test]
    public function differing_branch_lists_in_one_template_are_merged(): void
    {
        // The owner's decision (2026-10-15): the ESB holds branches per
        // template, so a product limited to one branch widens to the union.
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'Template SUKANDA', 'Purchase Request', 'STTSM, STPVJ', 'BEV', 'Milk', 'BD0028', 'BTL'],
            [2, 'Template SUKANDA', 'Purchase Request', ' stho ,STTSM,', 'FOD', 'Bega', 'AC0006', 'PACK'],
        ]));

        $this->assertSame(['STTSM', 'STPVJ', 'STHO'], $parsed->templates[0]->branchCodes);
        $this->assertSame([], $parsed->templates[0]->problems);
    }

    #[Test]
    public function rows_with_only_a_number_are_skipped(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'Template A', 'Purchase Request', 'STHO', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            [175, null, null, null, null, null, null, null],
            [176, '  ', null, null, null, '', null, null],
        ]));

        $this->assertSame([], $parsed->problems);
        $this->assertCount(1, $parsed->templates);
    }

    #[Test]
    public function values_are_trimmed_and_numbers_read_as_text(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, ' Template A ', ' purchase request ', 'STHO', ' VEG ', ' Pakcoy ', 1234.0, ' KG '],
        ]));

        $template = $parsed->templates[0];
        $this->assertSame('Template A', $template->name);
        $this->assertSame(DocumentTemplateType::PurchaseRequest, $template->type);
        $this->assertSame('1234', $template->lines[0]->productCode);
        $this->assertSame('KG', $template->lines[0]->unit);
        $this->assertSame('VEG', $template->lines[0]->category);
    }

    #[Test]
    public function every_documented_type_name_is_understood(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'A', 'Purchase Request', 'STHO', 'VEG', 'P', 'C1', 'KG'],
            [2, 'B', 'Usage Management', 'STHO', 'VEG', 'P', 'C1', 'KG'],
            [3, 'C', 'Item Journal', 'STHO', 'VEG', 'P', 'C1', 'KG'],
        ]));

        $this->assertSame(
            [DocumentTemplateType::PurchaseRequest, DocumentTemplateType::UsageManagement, DocumentTemplateType::ItemJournal],
            array_map(fn ($template) => $template->type, $parsed->templates),
        );
    }

    #[Test]
    public function problems_are_reported_on_the_template_with_their_row(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'Template A', 'Purchase Order', '', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            [2, 'Template A', 'Purchase Request', '', '', 'Bayam', '', ''],
            [3, 'Template A', 'Purchase Request', '', 'VEG 🥬', 'Pakcoy lagi', 'EC0080', 'KG'],
        ]));

        $problems = $parsed->templates[0]->problems;

        $this->assertContains('Row 3: unknown template type "Purchase Order" (expected Purchase Request, Usage Management, or Item Journal).', $problems);
        $this->assertContains('Row 4: product code is empty.', $problems);
        $this->assertContains('Row 4: unit is empty.', $problems);
        $this->assertContains('Row 4: category (used as the line name) is empty.', $problems);
        $this->assertContains('Row 5: the ERP does not accept emoji in the category.', $problems);
        $this->assertContains('Row 5: product EC0080 in KG is already on row 3.', $problems);
        $this->assertContains('No branch is listed for this template.', $problems);
    }

    #[Test]
    public function rows_of_one_template_with_different_types_are_a_problem(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'Template A', 'Purchase Request', 'STHO', 'VEG', 'P', 'C1', 'KG'],
            [2, 'Template A', 'Item Journal', 'STHO', 'VEG', 'Q', 'C2', 'KG'],
        ]));

        $this->assertContains('Row 4: type "Item Journal" differs from "Purchase Request" on row 3.', $parsed->templates[0]->problems);
    }

    #[Test]
    public function a_name_the_erp_would_refuse_is_a_problem(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, 'Template 🍚', 'Purchase Request', 'STHO', 'VEG', 'P', 'C1', 'KG'],
            [2, str_repeat('x', 256), 'Purchase Request', 'STHO', 'VEG', 'P', 'C1', 'KG'],
        ]));

        $this->assertContains('The ERP does not accept emoji in the template name.', $parsed->templates[0]->problems);
        $this->assertContains('The template name is longer than 255 characters.', $parsed->templates[1]->problems);
    }

    #[Test]
    public function a_product_row_without_a_template_name_is_a_sheet_problem(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse($this->sheet([
            [1, null, 'Purchase Request', 'STHO', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
        ]));

        $this->assertSame([], $parsed->templates);
        $this->assertSame(['Row 3: product EC0080 has no template name.'], $parsed->problems);
    }

    #[Test]
    public function headers_are_found_by_name_not_position(): void
    {
        $parsed = (new DocumentTemplateSheetParser)->parse([
            1 => ['Unit', 'product code', 'PRODUCT NAME', 'kategori', 'Branch  Aktif', 'Document Template Type', 'Document Template Name'],
            2 => ['KG', 'EC0080', 'Pakcoy', 'VEG', 'STHO', 'Purchase Request', 'Template A'],
        ]);

        $this->assertSame('Template A', $parsed->templates[0]->name);
        $this->assertSame('EC0080', $parsed->templates[0]->lines[0]->productCode);
        $this->assertSame(2, $parsed->templates[0]->lines[0]->rowNumber);
    }

    #[Test]
    public function a_sheet_without_the_expected_header_is_refused(): void
    {
        $this->expectException(InvalidDocumentTemplateSheet::class);
        $this->expectExceptionMessage('Missing columns: Kategori, Unit.');

        (new DocumentTemplateSheetParser)->parse([
            1 => ['Document Template Name', 'Document Template Type', 'Branch Aktif', 'Product Name', 'Product Code'],
        ]);
    }

    #[Test]
    public function a_sheet_without_any_template_is_refused(): void
    {
        $this->expectException(InvalidDocumentTemplateSheet::class);
        $this->expectExceptionMessage('The sheet has no template rows.');

        (new DocumentTemplateSheetParser)->parse($this->sheet([]));
    }
}
