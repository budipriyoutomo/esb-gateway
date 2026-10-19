<?php

namespace Tests\Fixtures;

use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Builds .xlsx files in the layout of the business's "Document Template
 * ESB_Supplier.xlsx" (ADR-023): a blank title row, the header, then one row
 * per product.
 */
final class DocumentTemplateSheet
{
    public const HEADER = ['No', 'Document Template Name', 'Document Template Type ', 'Branch Aktif ', 'Kategori', 'Product Name', 'Product Code', 'Unit'];

    /**
     * @param  list<list<mixed>>  $dataRows  Each: [No, name, type, branches, category, product name, code, unit].
     */
    public static function upload(array $dataRows, string $filename = 'Document Template ESB_Supplier.xlsx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'dts').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['', '', '', '', '', '', '', '']));
        $writer->addRow(Row::fromValues(self::HEADER));

        foreach ($dataRows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Two templates: one with a product limited to STHO (branches merged),
     * and one single-product template.
     *
     * @return list<list<mixed>>
     */
    public static function twoTemplates(): array
    {
        return [
            [1, 'Template SUKANDA', 'Purchase Request', 'STTSM', 'BEV', 'DIAMOND Fresh Milk', 'BD0028', 'BTL @0.946 LT'],
            [2, 'Template SUKANDA', 'Purchase Request', 'STHO', 'FOD', 'Bega Smokey BBQ', 'AC0006', 'PACK'],
            [null, null, null, null, null, null, null, null],
            [3, 'Template PR Olih', 'Purchase Request', 'STTSM, STHO', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            [175, null, null, null, null, null, null, null],
        ];
    }
}
