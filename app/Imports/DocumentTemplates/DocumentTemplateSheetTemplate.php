<?php

namespace App\Imports\DocumentTemplates;

use App\Domain\Canonical\Branch;
use App\Domain\Canonical\DocumentTemplateType;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The blank spreadsheet a person fills in for an import (ADR-023).
 *
 * - "Template", the first sheet -- the only one the import reads: the header
 *   alone, from DocumentTemplateSheetParser::COLUMNS, so the template and the
 *   parser cannot drift apart and no example is ever imported by mistake.
 * - "Petunjuk": what each column means, the rules the ERP applies, and an
 *   example.
 * - "Cabang ERP": the ERP's branch codes as of the download, since a code the
 *   ERP does not know blocks the whole template.
 *
 * Written in Indonesian, like the business's own sheet.
 */
class DocumentTemplateSheetTemplate
{
    public const FILENAME = 'Template Import Document Template.xlsx';

    /**
     * @param  list<Branch>|null  $branches  Null when the ERP could not be asked.
     */
    public function write(string $path, ?array $branches): void
    {
        $bold = (new Style)->setFontBold();

        $writer = new Writer;
        $writer->openToFile($path);

        $template = $writer->getCurrentSheet();
        $template->setName('Template');
        $template->setColumnWidth(6, 1);
        $template->setColumnWidthForRange(28, 2, 8);
        $writer->addRow(Row::fromValues(['No', ...array_values(DocumentTemplateSheetParser::COLUMNS)], $bold));

        $instructions = $writer->addNewSheetAndMakeItCurrent();
        $instructions->setName('Petunjuk');
        $instructions->setColumnWidth(28, 1);
        $instructions->setColumnWidth(90, 2);

        foreach ($this->instructions() as $row) {
            $writer->addRow(Row::fromValues($row, $row === [] || $row[1] !== '' ? null : $bold));
        }

        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Contoh (isi di sheet "Template", mulai baris 2):'], $bold));
        $writer->addRow(Row::fromValues(['No', ...array_values(DocumentTemplateSheetParser::COLUMNS)], $bold));

        foreach ($this->example() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $branchSheet = $writer->addNewSheetAndMakeItCurrent();
        $branchSheet->setName('Cabang ERP');
        $branchSheet->setColumnWidth(16, 1);
        $branchSheet->setColumnWidth(40, 2);

        if ($branches === null) {
            $writer->addRow(Row::fromValues(['The branch list could not be read from the ERP when this file was made. Download the template again later, or ask for the codes.']));
        } else {
            $writer->addRow(Row::fromValues(['Kode cabang', 'Nama cabang'], $bold));

            foreach ($branches as $branch) {
                $writer->addRow(Row::fromValues([$branch->code ?? '', $branch->name]));
            }
        }

        $writer->close();
    }

    /**
     * Column => explanation. A row with an empty explanation is a heading.
     *
     * @return list<list<string>>
     */
    private function instructions(): array
    {
        $types = implode(', ', array_map(
            static fn (DocumentTemplateType $type): string => $type->label(),
            DocumentTemplateType::cases(),
        ));

        return [
            ['Cara mengisi', ''],
            ['Sheet yang dibaca', 'Hanya sheet pertama ("Template"). Isi satu baris per produk, mulai baris 2. Baris dengan nama template yang sama menjadi satu template.'],
            ['Kolom', 'Penjelasan'],
            ['No', 'Nomor urut. Boleh dikosongkan; tidak dikirim ke ERP.'],
            [DocumentTemplateSheetParser::COLUMNS['name'], 'Nama template, maksimal 255 karakter, tanpa emoji. Nama yang sudah ada di ERP akan diblokir.'],
            [DocumentTemplateSheetParser::COLUMNS['type'], 'Salah satu: '.$types.'. Harus sama untuk semua baris satu template.'],
            [DocumentTemplateSheetParser::COLUMNS['branches'], 'Kode cabang ERP, dipisah koma (lihat sheet "Cabang ERP"). Cabang dari semua baris satu template digabung.'],
            [DocumentTemplateSheetParser::COLUMNS['category'], 'Dipakai sebagai nama baris (detail name) di ERP. Wajib, tanpa emoji.'],
            [DocumentTemplateSheetParser::COLUMNS['productName'], 'Nama produk, hanya untuk dibaca; ERP mencocokkan lewat kode.'],
            [DocumentTemplateSheetParser::COLUMNS['productCode'], 'Kode produk ERP, persis (huruf besar/kecil tidak berpengaruh). Rumus Excel boleh; yang dibaca hasilnya.'],
            [DocumentTemplateSheetParser::COLUMNS['unit'], 'Nama satuan persis seperti di ERP, termasuk isinya, mis. "PACK @ 0.3 KG" atau "CTN @ 48 PC". Spasi dan huruf besar/kecil tidak berpengaruh.'],
            ['', ''],
            ['Setelah upload', ''],
            ['1. Validate against ERP', 'Mengecek cabang, produk + satuan, dan nama template ke ERP. Tidak ada yang dikirim.'],
            ['2. Sync to ERP', 'Mengirim template yang "Ready". Yang diblokir atau ditolak tampil dengan alasannya.'],
        ];
    }

    /**
     * @return list<list<int|string>>
     */
    private function example(): array
    {
        return [
            [1, 'Template Supplier Sayur', 'Purchase Request', 'STTSM, STPVJ', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            [2, 'Template Supplier Sayur', 'Purchase Request', 'STTSM, STPVJ', 'VEG', 'Bawang Bombay', 'EC0005', 'KG'],
            [3, 'Template Supplier Minuman', 'Purchase Request', 'STTSM', 'BEV', 'Air Mineral Botol', 'BD0004', 'CTN @ 24 BTL'],
        ];
    }
}
