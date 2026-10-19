<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\DocumentTemplateDraft;
use App\Domain\Canonical\DocumentTemplateDraftLine;
use App\Domain\Canonical\DocumentTemplateType;
use App\Integrations\ESB\Mappers\DocumentTemplateMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDocumentTemplate;
use Tests\TestCase;

/**
 * ESB Core Master Document Template onto the canonical DocumentTemplate
 * ([ESB-016]).
 */
class DocumentTemplateMapperTest extends TestCase
{
    private function mapper(): DocumentTemplateMapper
    {
        return new DocumentTemplateMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $template = $this->mapper()->summaryFromRow(CoreDocumentTemplate::row());

        $this->assertSame('esb', $template->provider);
        $this->assertSame(12, $template->id);
        $this->assertSame('Weekly Kitchen Order', $template->name);
        $this->assertSame(['Purchase Request'], $template->typeNames);
        $this->assertSame(['Kemang', 'Senopati'], $template->branchNames);
        $this->assertTrue($template->active);
    }

    #[Test]
    public function a_list_row_reads_defensively(): void
    {
        $template = $this->mapper()->summaryFromRow([
            'requestTemplateID' => 0,
            'requestTemplateName' => null,
            'requestTemplateTypeNames' => ' , Item Journal,',
            'branchNames' => ['Kemang'],
            'flagActive' => 'maybe',
        ]);

        $this->assertNull($template->id);
        $this->assertSame('', $template->name);
        $this->assertSame(['Item Journal'], $template->typeNames);
        $this->assertSame([], $template->branchNames);
        $this->assertNull($template->active);
        $this->assertFalse($this->mapper()->summaryFromRow(['flagActive' => 0])->active);
        $this->assertTrue($this->mapper()->summaryFromRow(['flagActive' => true])->active);
    }

    #[Test]
    public function it_maps_the_detail(): void
    {
        $template = $this->mapper()->fromDetail(CoreDocumentTemplate::detail());

        $this->assertNotNull($template);
        $this->assertSame('esb', $template->provider);
        $this->assertSame(12, $template->id);
        $this->assertSame('Weekly Kitchen Order', $template->name);
        $this->assertSame(DocumentTemplateType::PurchaseRequest, $template->type);
        $this->assertTrue($template->active);
        $this->assertFalse($template->autoTransfer);
        $this->assertNull($template->allowZeroPricelist);
        $this->assertSame([3, 4], $template->branchIds);
        $this->assertSame([21], $template->costCenterIds);
        $this->assertSame('admin', $template->createdBy);
        $this->assertSame('2026-01-10 08:00:00', $template->createdAt);
        $this->assertNull($template->editedBy);
        $this->assertNull($template->editedAt);
        $this->assertNull($template->syncedAt);

        $this->assertCount(2, $template->lines);

        $line = $template->lines[0];
        $this->assertSame(301, $line->lineId);
        $this->assertSame(5001, $line->productDetailId);
        $this->assertSame('RM-0001', $line->productCode);
        $this->assertSame('Beras Premium', $line->productName);
        $this->assertSame('Beras', $line->name);
        $this->assertSame('KG', $line->uomName);
        $this->assertSame('Dry Goods', $line->categoryName);
        $this->assertSame(7, $line->purposeId);
        $this->assertSame(14500.25, $line->lastCost);
        $this->assertTrue($line->active);

        $second = $template->lines[1];
        $this->assertNull($second->name);
        $this->assertNull($second->purposeId);
        $this->assertNull($second->lastCost);
        $this->assertFalse($second->active);
    }

    #[Test]
    public function an_undocumented_type_id_is_unknown(): void
    {
        $template = $this->mapper()->fromDetail(CoreDocumentTemplate::detail(['requestTemplateTypeID' => 9]));

        $this->assertNotNull($template);
        $this->assertNull($template->type);
    }

    #[Test]
    public function detail_lines_read_defensively(): void
    {
        $template = $this->mapper()->fromDetail(CoreDocumentTemplate::detail([
            'requestTemplateDetails' => [
                'not-a-row',
                ['ID' => 0, 'productName' => null, 'lastHpp' => 'abc'],
            ],
        ]));

        $this->assertNotNull($template);
        $this->assertCount(1, $template->lines);
        $this->assertNull($template->lines[0]->lineId);
        $this->assertSame('', $template->lines[0]->productName);
        $this->assertNull($template->lines[0]->lastCost);

        $none = $this->mapper()->fromDetail(CoreDocumentTemplate::detail(['requestTemplateDetails' => null]));
        $this->assertNotNull($none);
        $this->assertSame([], $none->lines);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_template(): void
    {
        $this->assertNull($this->mapper()->fromDetail([]));
        $this->assertNull($this->mapper()->fromDetail(CoreDocumentTemplate::detail(['requestTemplateID' => 0])));
    }

    // ================================================================= create

    #[Test]
    public function it_builds_the_create_body(): void
    {
        $body = $this->mapper()->toRequestBody(new DocumentTemplateDraft(
            name: 'Weekly Kitchen Order',
            type: DocumentTemplateType::UsageManagement,
            active: false,
            branchIds: [3, 4],
            lines: [
                new DocumentTemplateDraftLine(productDetailId: 5001, name: 'Beras', purposeId: 7),
                new DocumentTemplateDraftLine(productDetailId: 5002, name: 'Minyak'),
            ],
        ));

        $this->assertSame([
            'requestTemplateName' => 'Weekly Kitchen Order',
            'requestTemplateTypeID' => 2,
            'flagActive' => 0,
            'branchIDs' => [3, 4],
            'requestTemplateDetails' => [
                ['productDetailID' => 5001, 'requestTemplateDetailName' => 'Beras', 'purposeID' => 7],
                ['productDetailID' => 5002, 'requestTemplateDetailName' => 'Minyak'],
            ],
        ], $body);
    }

    #[Test]
    public function the_created_id_is_read_only_when_it_is_a_positive_integer(): void
    {
        $this->assertSame(55, $this->mapper()->createdIdFrom(['requestTemplateID' => 55]));
        $this->assertSame(55, $this->mapper()->createdIdFrom(['requestTemplateID' => '55']));
        $this->assertNull($this->mapper()->createdIdFrom([]));
        $this->assertNull($this->mapper()->createdIdFrom(['requestTemplateID' => 0]));
        $this->assertNull($this->mapper()->createdIdFrom(['requestTemplateID' => '../1']));
        // The vendor says only "returns its ID"; another key is not guessed at.
        $this->assertNull($this->mapper()->createdIdFrom(['id' => 55]));
    }

    #[Test]
    public function product_ids_are_read_from_list_rows_optionally_for_one_exact_code(): void
    {
        $rows = [
            ['productID' => 1086, 'productCode' => 'EC0080', 'productName' => 'Pakcoy'],
            ['productID' => 1090, 'productCode' => 'EC00801', 'productName' => 'Pakcoy Baby'],
            ['productID' => 0, 'productCode' => 'EC0080'],
            ['productID' => 1091, 'productCode' => 'ec0080 ', 'productName' => 'Pakcoy lower'],
        ];

        $this->assertSame([1086, 1090, 1091], $this->mapper()->productIdsFromRows($rows));
        // The ESB matches productCode partially; only the exact code counts.
        $this->assertSame([1086, 1091], $this->mapper()->productIdsFromRows($rows, 'EC0080'));
    }

    #[Test]
    public function a_products_active_units_become_product_detail_options(): void
    {
        $options = $this->mapper()->productDetailsFromProduct([
            'productCode' => 'EC0080',
            'productName' => 'Pakcoy',
            'flagActive' => true,
            'productDetails' => [
                ['productDetailID' => 2030, 'uomName' => 'GR', 'flagActive' => false],
                ['productDetailID' => 2031, 'uomName' => 'KG', 'flagActive' => true],
                ['productDetailID' => 0, 'uomName' => 'PC', 'flagActive' => true],
                ['productDetailID' => 2032, 'uomName' => '', 'flagActive' => 1],
                'not-a-row',
            ],
        ]);

        $this->assertCount(2, $options);
        $this->assertSame(2031, $options[0]->productDetailId);
        $this->assertSame('EC0080', $options[0]->productCode);
        $this->assertSame('Pakcoy', $options[0]->productName);
        $this->assertSame('KG', $options[0]->uomName);
        $this->assertSame(2032, $options[1]->productDetailId);
        $this->assertNull($options[1]->uomName);

        $this->assertSame([], $this->mapper()->productDetailsFromProduct(['productDetails' => null]));
    }
}
