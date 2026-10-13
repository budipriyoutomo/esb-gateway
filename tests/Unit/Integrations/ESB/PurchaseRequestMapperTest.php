<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\PurchaseRequestDraft;
use App\Domain\Canonical\PurchaseRequestDraftLine;
use App\Integrations\ESB\Mappers\PurchaseRequestMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Maps rows from GET /purchase/purchase-request and the result of
 * GET /purchase/purchase-request/{purchaseRequestNum} onto the canonical shape.
 *
 * Fields are transcribed from the vendor's OpenAPI description for these two
 * operations. The vendor's examples are zero-valued placeholders, so date
 * formats and status IDs are not documented: dates are carried as the strings
 * the ESB sent, and status is carried as the ESB's own id and name.
 */
class PurchaseRequestMapperTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function listRow(): array
    {
        return [
            'additionalInfo' => 'Restock bar',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'createdDate' => '2026-03-26 09:15:00',
            'purchaseRequestDate' => '2026-03-26',
            'purchaseRequestNum' => 'PR202603260005',
            'requestTemplateID' => 7,
            'requestTemplateName' => 'Weekly bar',
            'requiredDate' => '2026-03-28',
            'statusID' => 2,
            'statusName' => 'Authorized',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailResult(): array
    {
        return [
            'additionalInfo' => 'Restock bar',
            'authorizedBy' => '41',
            'authorizedByName' => 'Rina',
            'authorizedDate' => '2026-03-26 11:00:00',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'costCenterID' => 5,
            'costCenterName' => 'Bar',
            'createdBy' => '12',
            'createdByName' => 'Andi',
            'createdDate' => '2026-03-26 09:15:00',
            'editedBy' => '',
            'editedByName' => '',
            'editedDate' => '',
            'imageAttachments' => ['https://storage.esb.co.id/attachments/pr/1.jpg'],
            'isTemplate' => false,
            'print' => [
                'companyLogoUrl' => 'https://storage.esb.co.id/logo.png',
                'signatureSetting' => ['flagAlignment' => false, 'horizontalAlignment' => '', 'signatures' => []],
            ],
            'projectID' => 0,
            'projectName' => '',
            'purchaseNums' => ['PO202603270001'],
            'purchaseRequestDate' => '2026-03-26',
            'purchaseRequestDetails' => [
                [
                    'maxBranchProductQty' => 50,
                    'minBranchProductQty' => 10,
                    'notes' => 'cold',
                    'productCode' => 'Air_mineral',
                    'productDetailID' => 901,
                    'productID' => 75,
                    'productName' => 'Air mineral',
                    'qty' => 24.5,
                    'requestProcessID' => 1,
                    'requestTemplateDetailName' => 'Drinks',
                    'requestTemplateID' => '7',
                    'stock' => 6,
                    'suggestionQty' => 20,
                    'uomName' => 'Bottle',
                ],
            ],
            'purchaseRequestNum' => 'PR202603260005',
            'requestTemplateID' => 7,
            'requiredDate' => '2026-03-28',
            'statusID' => 2,
            'statusName' => 'Authorized',
            'transferNums' => [],
        ];
    }

    // ------------------------------------------------------------------ list

    #[Test]
    public function it_maps_a_documented_list_row(): void
    {
        $summary = app(PurchaseRequestMapper::class)->summaryFromRow($this->listRow());

        $this->assertSame('esb', $summary->provider);
        $this->assertSame('PR202603260005', $summary->number);
        $this->assertSame('2026-03-26', $summary->date);
        $this->assertSame('2026-03-28', $summary->requiredDate);
        $this->assertSame('2026-03-26 09:15:00', $summary->createdAt);
        $this->assertSame(3, $summary->branchId);
        $this->assertSame('Outlet Senopati', $summary->branchName);
        $this->assertSame(7, $summary->templateId);
        $this->assertSame('Weekly bar', $summary->templateName);
        $this->assertSame(2, $summary->statusId);
        $this->assertSame('Authorized', $summary->statusName);
        $this->assertSame('Restock bar', $summary->notes);
    }

    #[Test]
    public function a_list_row_missing_fields_does_not_throw(): void
    {
        $summary = app(PurchaseRequestMapper::class)->summaryFromRow([]);

        $this->assertSame('', $summary->number);
        $this->assertNull($summary->date);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->statusName);
    }

    #[Test]
    public function empty_strings_and_wrong_types_become_null(): void
    {
        $summary = app(PurchaseRequestMapper::class)->summaryFromRow([
            'purchaseRequestNum' => 'PR1',
            'branchName' => '',
            'branchID' => 'not-a-number',
            'statusName' => ['nested'],
        ]);

        $this->assertNull($summary->branchName);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->statusName);
    }

    // ---------------------------------------------------------------- detail

    #[Test]
    public function it_maps_the_documented_detail_header(): void
    {
        $request = app(PurchaseRequestMapper::class)->fromDetail($this->detailResult());

        $this->assertSame('esb', $request->provider);
        $this->assertSame('PR202603260005', $request->number);
        $this->assertSame('Outlet Senopati', $request->branchName);
        $this->assertSame(5, $request->costCenterId);
        $this->assertSame('Bar', $request->costCenterName);
        $this->assertSame('Andi', $request->createdByName);
        $this->assertSame('Rina', $request->authorizedByName);
        $this->assertSame('2026-03-26 11:00:00', $request->authorizedAt);
        $this->assertNull($request->editedByName);
        $this->assertNull($request->editedAt);
        $this->assertFalse($request->isTemplate);
        $this->assertSame(['PO202603270001'], $request->purchaseNumbers);
        $this->assertSame([], $request->transferNumbers);
    }

    #[Test]
    public function a_zero_id_means_absent(): void
    {
        // The vendor sends projectID 0 with an empty projectName when no
        // project is attached. Showing "project #0" would invent one.
        $request = app(PurchaseRequestMapper::class)->fromDetail($this->detailResult());

        $this->assertNull($request->projectId);
        $this->assertNull($request->projectName);
    }

    #[Test]
    public function it_maps_the_detail_lines(): void
    {
        $request = app(PurchaseRequestMapper::class)->fromDetail($this->detailResult());

        $this->assertCount(1, $request->lines);

        $line = $request->lines[0];
        $this->assertSame(75, $line->productId);
        $this->assertSame(901, $line->productDetailId);
        $this->assertSame('Air_mineral', $line->productCode);
        $this->assertSame('Air mineral', $line->productName);
        $this->assertSame('Bottle', $line->uomName);
        $this->assertSame(24.5, $line->qty);
        $this->assertSame(6.0, $line->stock);
        $this->assertSame(20.0, $line->suggestionQty);
        $this->assertSame(10.0, $line->minQty);
        $this->assertSame(50.0, $line->maxQty);
        $this->assertSame('cold', $line->notes);
    }

    #[Test]
    public function print_layout_and_attachment_urls_are_not_carried(): void
    {
        // Both hold absolute URLs on the vendor's storage. The print block is
        // the ESB's own document layout, and attachments are deferred until
        // there is a decision on how the gateway serves them (ADR-006: the
        // browser does not talk to the ESB's hosts).
        $request = app(PurchaseRequestMapper::class)->fromDetail($this->detailResult());

        $this->assertStringNotContainsString('esb.co.id', (string) json_encode($request));
    }

    #[Test]
    public function malformed_lines_and_document_numbers_are_skipped(): void
    {
        $detail = $this->detailResult();
        $detail['purchaseRequestDetails'] = ['not-a-row', ['productName' => 'Only a name']];
        $detail['purchaseNums'] = ['PO1', '', 42, null];
        $detail['transferNums'] = 'not-a-list';

        $request = app(PurchaseRequestMapper::class)->fromDetail($detail);

        $this->assertCount(1, $request->lines);
        $this->assertSame('Only a name', $request->lines[0]->productName);
        $this->assertSame(0.0, $request->lines[0]->qty);
        $this->assertSame(['PO1'], $request->purchaseNumbers);
        $this->assertSame([], $request->transferNumbers);
    }

    #[Test]
    public function a_detail_line_carries_its_request_process(): void
    {
        // Update sends every line back with its requestProcessID, so the
        // edit form needs it; the detail read is the only place it comes from.
        $request = app(PurchaseRequestMapper::class)->fromDetail($this->detailResult());

        $this->assertSame(1, $request->lines[0]->requestProcessId);
    }

    // --------------------------------------------------------------- lookups

    #[Test]
    public function it_maps_branches_and_skips_rows_without_an_id(): void
    {
        $branches = app(PurchaseRequestMapper::class)->branchesFrom([
            ['branchCode' => 'SNP', 'branchID' => 3, 'branchName' => 'Outlet Senopati'],
            ['branchCode' => '', 'branchID' => 0, 'branchName' => 'Placeholder'],
            'not-a-row',
        ]);

        $this->assertCount(1, $branches);
        $this->assertSame(3, $branches[0]->id);
        $this->assertSame('SNP', $branches[0]->code);
        $this->assertSame('Outlet Senopati', $branches[0]->name);
    }

    #[Test]
    public function it_maps_request_templates(): void
    {
        $templates = app(PurchaseRequestMapper::class)->templatesFrom([
            ['requestTemplateID' => 7, 'requestTemplateName' => 'Weekly bar', 'requestTemplateTypeID' => 1],
            ['requestTemplateID' => 0, 'requestTemplateName' => ''],
        ]);

        $this->assertCount(1, $templates);
        $this->assertSame(7, $templates[0]->id);
        $this->assertSame('Weekly bar', $templates[0]->name);
    }

    #[Test]
    public function it_maps_template_lines_and_skips_those_that_cannot_be_requested(): void
    {
        $lines = app(PurchaseRequestMapper::class)->templateLinesFrom([
            [
                'categoryTypeID' => 1,
                'conversionQty' => 1,
                'maxBranchProductQty' => 50,
                'minBranchProductQty' => 10,
                'need' => 0,
                'notes' => '',
                'productCode' => 'Air_mineral',
                'productDetailID' => 901,
                'productID' => 75,
                'productName' => 'Air mineral',
                'qty' => 0,
                'requestProcessID' => 1,
                'requestProcessName' => 'Purchase',
                'requestTemplateDetailName' => 'Drinks',
                'requestTemplateID' => 7,
                'stock' => 6,
                'suggestionQty' => 20,
                'uomName' => 'Bottle',
                'usage' => 0,
            ],
            // Create requires both ids on every line; a template row without
            // them can only produce a request the ERP refuses.
            ['productDetailID' => 902, 'requestProcessID' => 0, 'productName' => 'No process'],
            ['productDetailID' => 0, 'requestProcessID' => 1, 'productName' => 'No detail'],
        ]);

        $this->assertCount(1, $lines);
        $this->assertSame(901, $lines[0]->productDetailId);
        $this->assertSame(1, $lines[0]->requestProcessId);
        $this->assertSame('Purchase', $lines[0]->requestProcessName);
        $this->assertSame('Drinks', $lines[0]->group);
        $this->assertSame('Air_mineral', $lines[0]->productCode);
        $this->assertSame('Bottle', $lines[0]->uomName);
        $this->assertSame(6.0, $lines[0]->stock);
        $this->assertSame(20.0, $lines[0]->suggestionQty);
        $this->assertSame(10.0, $lines[0]->minQty);
        $this->assertSame(50.0, $lines[0]->maxQty);
    }

    // ----------------------------------------------------------- write body

    #[Test]
    public function a_draft_becomes_the_documented_request_body(): void
    {
        $body = app(PurchaseRequestMapper::class)->toRequestBody(new PurchaseRequestDraft(
            branchId: 3,
            date: '2026-03-26',
            requiredDate: '2026-03-28',
            lines: [
                new PurchaseRequestDraftLine(productDetailId: 901, requestProcessId: 1, qty: 24.5, notes: 'cold'),
                new PurchaseRequestDraftLine(productDetailId: 902, requestProcessId: 2, qty: 3.0),
            ],
            templateId: 7,
            notes: 'Restock bar',
        ));

        $this->assertSame([
            'branchID' => 3,
            'purchaseRequestDate' => '2026-03-26',
            'requiredDate' => '2026-03-28',
            'isTemplate' => false,
            'purchaseRequestDetails' => [
                ['productDetailID' => 901, 'requestProcessID' => 1, 'qty' => 24.5, 'notes' => 'cold'],
                ['productDetailID' => 902, 'requestProcessID' => 2, 'qty' => 3.0],
            ],
            'requestTemplateID' => 7,
            'additionalInfo' => 'Restock bar',
        ], $body);
    }

    #[Test]
    public function references_carried_through_an_edit_are_sent_back(): void
    {
        // PUT replaces the document. A reference the form does not edit must
        // still be sent, or the update would silently detach it.
        $body = app(PurchaseRequestMapper::class)->toRequestBody(new PurchaseRequestDraft(
            branchId: 3,
            date: '2026-03-26',
            requiredDate: '2026-03-28',
            lines: [new PurchaseRequestDraftLine(productDetailId: 901, requestProcessId: 1, qty: 1.0)],
            costCenterId: 5,
            projectId: 9,
            isTemplate: true,
        ));

        $this->assertSame(5, $body['costCenterID']);
        $this->assertSame(9, $body['projectID']);
        $this->assertTrue($body['isTemplate']);
        $this->assertArrayNotHasKey('requestTemplateID', $body);
        $this->assertArrayNotHasKey('additionalInfo', $body);
    }
}
