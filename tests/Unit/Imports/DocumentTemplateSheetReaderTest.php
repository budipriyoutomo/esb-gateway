<?php

namespace Tests\Unit\Imports;

use App\Imports\DocumentTemplates\DocumentTemplateSheetReader;
use App\Imports\DocumentTemplates\InvalidDocumentTemplateSheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

/**
 * Reading the uploaded .xlsx (ADR-023).
 *
 * The business's sheet fills Product Code with
 * =VLOOKUP($F19,[1]Product!$B:$F,2,0) against another workbook. Excel saves
 * the last result next to the formula; that result is what counts, never the
 * formula text (found on staging, 2026-10-15).
 */
class DocumentTemplateSheetReaderTest extends TestCase
{
    /**
     * A minimal workbook whose sheet XML is given as is, so a formula cell can
     * carry a cached value the way Excel writes it.
     */
    private function workbook(string $sheetData): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dtr').'.xlsx';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetData.'</sheetData></worksheet>');
        $zip->close();

        return $path;
    }

    #[Test]
    public function a_formula_cell_reads_as_its_saved_result_not_its_formula(): void
    {
        $path = $this->workbook(
            '<row r="2"><c r="A2" t="inlineStr"><is><t>Product Name</t></is></c><c r="B2" t="inlineStr"><is><t>Product Code</t></is></c></row>'
            .'<row r="19"><c r="A19" t="inlineStr"><is><t>Pakcoy</t></is></c>'
            .'<c r="B19" t="str"><f>VLOOKUP($F19,[1]Product!$B:$F,2,0)</f><v>EC0080</v></c></row>',
        );

        $rows = (new DocumentTemplateSheetReader)->read($path);

        $this->assertSame(['Pakcoy', 'EC0080'], $rows[19]);
    }

    #[Test]
    public function row_numbers_match_the_sheet_even_across_empty_rows(): void
    {
        $path = $this->workbook(
            '<row r="2"><c r="A2" t="inlineStr"><is><t>header</t></is></c></row>'
            .'<row r="5"><c r="A5" t="inlineStr"><is><t>after a gap</t></is></c></row>',
        );

        $rows = (new DocumentTemplateSheetReader)->read($path);

        $this->assertSame(['after a gap'], $rows[5]);
        $this->assertSame(['header'], $rows[2]);
    }

    #[Test]
    public function a_formula_whose_result_was_an_error_reads_as_empty(): void
    {
        $path = $this->workbook(
            '<row r="3"><c r="A3" t="e"><f>VLOOKUP(X1,Y:Z,2,0)</f><v>#N/A</v></c><c r="B3" t="inlineStr"><is><t>KG</t></is></c></row>',
        );

        $rows = (new DocumentTemplateSheetReader)->read($path);

        $this->assertSame([null, 'KG'], $rows[3]);
    }

    #[Test]
    public function a_file_that_is_not_a_workbook_is_refused(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dtr');
        file_put_contents($path, 'PK'."\x03\x04".'not really a zip');

        $this->expectException(InvalidDocumentTemplateSheet::class);
        $this->expectExceptionMessage('The file could not be read as an .xlsx workbook.');

        (new DocumentTemplateSheetReader)->read($path);
    }
}
