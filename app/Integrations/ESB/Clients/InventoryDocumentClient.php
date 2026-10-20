<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\InventoryCarryOver;
use App\Domain\Canonical\InventoryDocument;
use App\Domain\Canonical\InventoryDocumentPage;
use App\Domain\Canonical\InventoryDocumentQuery;
use App\Domain\Canonical\InventoryDraft;
use App\Domain\Canonical\InventoryDraftLine;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Mappers\InventoryDocumentMapper;
use LogicException;

/**
 * Adapter for the ESB Core inventory documents: reads ([ESB-033], ADR-026)
 * and state changes ([ESB-034]). Writes are sent once (EsbClient never
 * retries a PATCH / DELETE).
 */
class InventoryDocumentClient implements InventoryDocumentSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly InventoryDocumentMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request/get
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery/get
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt/get
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal/get
     * @see https://developers.esb.co.id/esb-core/#/paths/simple-transfer/get
     */
    public function listDocuments(InventoryKind $kind, InventoryDocumentQuery $query): InventoryDocumentPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            $this->path($kind),
            array_filter([
                $this->numberParam($kind) => $query->number,
                'dateFrom' => $query->dateFrom,
                'dateTo' => $query->dateTo,
                'statusID' => $query->statusId,
                ...($this->branchParam($kind) === null ? [] : [$this->branchParam($kind) => $query->branchId]),
                ...($kind->searchable() ? ['indexSearch' => $query->search] : []),
                'page' => $query->page,
                'limit' => $query->limit,
            ], static fn (mixed $value): bool => $value !== null),
            operation: $kind->value.'.list',
        );

        return new InventoryDocumentPage(
            documents: $this->mapper->summariesFromRows($kind, $envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-get-details-outstanding-qty/get
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-stock-check-goodsReceiptNum/get
     * @see https://developers.esb.co.id/esb-core/#/paths/simple-transfer-stock-check-simpleTransferNum/get
     */
    public function findDocument(InventoryKind $kind, string $number): ?InventoryDocument
    {
        $envelope = $this->client->get(
            self::FAMILY,
            $this->path($kind).'/'.rawurlencode($number),
            operation: $kind->value.'.view',
        );

        $document = $this->mapper->fromDetail($kind, $envelope->resultArray());

        if ($document === null) {
            return null;
        }

        return match ($kind) {
            InventoryKind::GoodsDeliveries => $this->withOutstanding($document),
            InventoryKind::GoodsReceipts => $this->withStockCheck($document, '/inventory/goods-receipt/stock-check/'),
            InventoryKind::SimpleTransfers => $this->withStockCheck($document, '/simple-transfer/stock-check/'),
            default => $document,
        };
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--reject/patch
     */
    public function decideDocument(InventoryKind $kind, string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $segment = $decision === ApprovalDecision::Authorize ? 'authorize' : 'reject';
        $body = $decision === ApprovalDecision::Reject && $kind->rejectTakesReason() && $reason !== null
            ? ['reason' => $reason]
            : [];

        $this->client->patch(
            self::FAMILY,
            $this->path($kind).'/'.rawurlencode($number).'/'.$segment,
            $body,
            operation: $kind->value.'.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum/delete
     */
    public function deleteDocument(InventoryKind $kind, string $number): void
    {
        $this->client->delete(
            self::FAMILY,
            $this->path($kind).'/'.rawurlencode($number),
            operation: $kind->value.'.delete',
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request/post
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal/post
     * @see https://developers.esb.co.id/esb-core/#/paths/simple-transfer/post
     */
    public function createDocument(InventoryKind $kind, InventoryDraft $draft): ?string
    {
        $envelope = $this->client->post(
            self::FAMILY,
            $this->path($kind),
            $this->body($kind, $draft, new InventoryCarryOver),
            operation: $kind->value.'.create',
        );

        // The item journal documents itemJournalNum; the others document "a
        // map of strings", so only their detail's own number field is read.
        $number = $envelope->resultArray()[$this->numberParam($kind)] ?? null;

        return is_string($number) && trim($number) !== '' ? trim($number) : null;
    }

    /**
     * The transfer request's update is a PATCH of the whole request; the
     * others are a PUT.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum/put
     * @see https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum/put
     */
    public function updateDocument(InventoryKind $kind, string $number, InventoryDraft $draft, InventoryCarryOver $carryOver): void
    {
        $path = $this->path($kind).'/'.rawurlencode($number);
        $body = $this->body($kind, $draft, $carryOver);
        $operation = $kind->value.'.update';

        if ($kind === InventoryKind::GoodsTransferRequests) {
            $this->client->patch(self::FAMILY, $path, $body, operation: $operation);

            return;
        }

        $this->client->put(self::FAMILY, $path, $body, operation: $operation);
    }

    /**
     * The vendor's create / update body. An optional value the draft does not
     * have is left out, except the notes (sent as "" so an edit can clear
     * them) and the transfer request's purchase requests (the vendor asks
     * for null when there are none).
     *
     * @return array<string, mixed>
     */
    private function body(InventoryKind $kind, InventoryDraft $draft, InventoryCarryOver $carryOver): array
    {
        $optional = static fn (array $values): array => array_filter($values, static fn (mixed $value): bool => $value !== null);

        return match ($kind) {
            InventoryKind::GoodsTransferRequests => [
                'transferDate' => $draft->date,
                'originBranchID' => $draft->branchId,
                ...$optional(['originLocationID' => $draft->locationId]),
                'destinationBranchID' => $draft->destinationBranchId,
                // Inventory (goods & services); asset requests are not made here.
                'categoryTypeID' => 1,
                'additionalInfo' => $draft->notes ?? '',
                'purchaseRequestNum' => $carryOver->purchaseRequestNumbers === [] ? null : $carryOver->purchaseRequestNumbers,
                'transferDetailAssets' => [],
                'transferDetails' => array_map(static fn (InventoryDraftLine $line): array => [
                    'productDetailID' => $line->productDetailId,
                    'qty' => $line->qty,
                    ...$optional(['requestQty' => $line->requestQty]),
                ], $draft->lines),
            ],
            InventoryKind::ItemJournals => [
                'itemJournalDate' => $draft->date,
                'branchID' => $draft->branchId,
                'locationID' => $draft->locationId,
                'additionalInfo' => $draft->notes ?? '',
                'itemJournalDetails' => array_map(static fn (InventoryDraftLine $line): array => [
                    'productDetailID' => $line->productDetailId,
                    'qty' => $line->qty,
                    ...$optional(['purposeID' => $line->purposeId, 'hpp' => $line->cost]),
                ], $draft->lines),
                ...$optional(['requestTemplateID' => $carryOver->requestTemplateId]),
            ],
            InventoryKind::SimpleTransfers => [
                'simpleTransferDate' => $draft->date,
                'originLocationID' => $draft->locationId,
                'destinationLocationID' => $draft->destinationLocationId,
                'additionalInfo' => $draft->notes ?? '',
                'simpleTransferDetails' => array_map(static fn (InventoryDraftLine $line): array => [
                    'productDetailID' => $line->productDetailId,
                    'qty' => $line->qty,
                ], $draft->lines),
                ...$optional(['costCenterID' => $carryOver->costCenterId, 'projectID' => $carryOver->projectId]),
            ],
            default => throw new LogicException("{$kind->value} is not created or edited through the gateway."),
        };
    }

    /**
     * The vendor's "existing" list (attachment URLs to keep) is never sent:
     * the gateway does not hold those URLs. The caller attaches only while a
     * document whose upload replaces has none.
     *
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--attachment/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--attachment/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--attachment/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum--attachment/patch
     */
    public function attachFiles(InventoryKind $kind, string $number, array $files): void
    {
        if (! $kind->takesAttachments()) {
            throw new LogicException("{$kind->value} takes no attachments through the gateway.");
        }

        $this->client->uploadFiles(
            self::FAMILY,
            $this->path($kind).'/'.rawurlencode($number).'/attachment',
            'files',
            $files,
            method: 'PATCH',
            operation: $kind->value.'.attach',
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--attachment/delete
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--attachment/delete
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--attachment/delete
     */
    public function deleteAttachments(InventoryKind $kind, string $number): void
    {
        if (! $kind->attachmentsRemovable()) {
            throw new LogicException("{$kind->value} attachments cannot be removed through the gateway.");
        }

        $this->client->delete(
            self::FAMILY,
            $this->path($kind).'/'.rawurlencode($number).'/attachment',
            operation: $kind->value.'.detach',
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--finish/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--unfinished/patch
     */
    public function completeTransferRequest(string $number, bool $finished): void
    {
        $this->client->patch(
            self::FAMILY,
            $this->path(InventoryKind::GoodsTransferRequests).'/'.rawurlencode($number).($finished ? '/finish' : '/unfinished'),
            operation: InventoryKind::GoodsTransferRequests->value.($finished ? '.finish' : '.reopen'),
        );
    }

    /**
     * The vendor requires the reference and its type; a delivery without
     * them has nothing outstanding to ask about.
     */
    private function withOutstanding(InventoryDocument $document): InventoryDocument
    {
        if ($document->reference === null || $document->transactionType === null) {
            return $document;
        }

        try {
            $envelope = $this->client->get(self::FAMILY, '/inventory/goods-delivery/get-details-outstanding-qty', [
                'goodsDeliveryNum' => $document->number,
                'refNum' => $document->reference,
                'transType' => $document->transactionType,
            ], operation: 'goods-deliveries.outstanding');
        } catch (EsbNotFoundException) {
            return $document;
        } catch (EsbException) {
            // Supplementary: the document itself was read.
            return $document;
        }

        return $document->withOutstanding($this->mapper->outstandingFrom($envelope->resultArray()));
    }

    private function withStockCheck(InventoryDocument $document, string $path): InventoryDocument
    {
        try {
            $envelope = $this->client->get(self::FAMILY, $path.rawurlencode($document->number), operation: $document->kind.'.stock-check');
        } catch (EsbException) {
            // Supplementary: the document itself was read.
            return $document;
        }

        return $document->withStockUsed($this->mapper->stockUsedFrom($envelope->resultArray()));
    }

    private function path(InventoryKind $kind): string
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => '/inventory/goods-transfer-request',
            InventoryKind::GoodsDeliveries => '/inventory/goods-delivery',
            InventoryKind::GoodsReceipts => '/inventory/goods-receipt',
            InventoryKind::ItemJournals => '/inventory/item-journal',
            InventoryKind::SimpleTransfers => '/simple-transfer',
        };
    }

    private function numberParam(InventoryKind $kind): string
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => 'transferNum',
            InventoryKind::GoodsDeliveries => 'goodsDeliveryNum',
            InventoryKind::GoodsReceipts => 'goodsReceiptNum',
            InventoryKind::ItemJournals => 'itemJournalNum',
            InventoryKind::SimpleTransfers => 'simpleTransferNum',
        };
    }

    private function branchParam(InventoryKind $kind): ?string
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => 'originBranchID',
            InventoryKind::SimpleTransfers => null,
            default => 'branchID',
        };
    }
}
