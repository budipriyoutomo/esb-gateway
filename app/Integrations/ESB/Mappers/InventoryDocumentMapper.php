<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\InventoryCarryOver;
use App\Domain\Canonical\InventoryDocument;
use App\Domain\Canonical\InventoryDocumentSummary;
use App\Domain\Canonical\InventoryKind;
use App\Domain\Canonical\InventoryLine;
use App\Domain\Canonical\InventoryLinkedDocument;
use App\Domain\Canonical\InventoryParty;
use App\Integrations\ESB\Clients\InventoryDocumentClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * The five ESB Core inventory documents onto one canonical shape ([ESB-033],
 * ADR-026). Field names differ per document; their meaning does not.
 *
 * The vendor's examples are zero-valued, so 0 / "" ids and texts are "none".
 * Quantities are kept as sent, 0 included: 0 received is a real quantity.
 */
class InventoryDocumentMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<mixed>  $rows
     * @return list<InventoryDocumentSummary>
     */
    public function summariesFromRows(InventoryKind $kind, array $rows): array
    {
        $summaries = [];

        foreach ($rows as $row) {
            $summary = is_array($row) ? $this->summaryFromRow($kind, $row) : null;

            if ($summary !== null) {
                $summaries[] = $summary;
            }
        }

        return $summaries;
    }

    /**
     * A list row, or null when it carries neither a number nor a reference.
     * The goods delivery and receipt lists include "Pending" rows (status 33):
     * references not yet delivered / received, which have no number of their
     * own but count toward the vendor's pagination.
     *
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(InventoryKind $kind, array $row): ?InventoryDocumentSummary
    {
        $number = $this->nullableString($row[$this->numberField($kind)] ?? null);
        $reference = $this->nullableString(match ($kind) {
            InventoryKind::GoodsTransferRequests => $row['purchaseRequestNum'] ?? null,
            InventoryKind::GoodsDeliveries => $row['referenceNumber'] ?? null,
            InventoryKind::GoodsReceipts => $row['refNum'] ?? null,
            default => null,
        });

        $pendingKinds = [InventoryKind::GoodsDeliveries, InventoryKind::GoodsReceipts];

        if ($number === null && ($reference === null || ! in_array($kind, $pendingKinds, true))) {
            return null;
        }

        [$from, $to] = match ($kind) {
            InventoryKind::GoodsTransferRequests => [
                $this->party(branchName: $row['originBranch'] ?? null),
                $this->party(branchName: $row['destinationBranch'] ?? null),
            ],
            InventoryKind::GoodsDeliveries => [
                $this->party(branchId: $row['originBranchID'] ?? null, branchName: $row['originBranchName'] ?? null),
                $this->party(branchId: $row['destinationBranchID'] ?? null, branchName: $row['destBranchName'] ?? null, name: $row['customerName'] ?? null),
            ],
            InventoryKind::GoodsReceipts => [
                $this->party(name: $row['sourceName'] ?? null),
                $this->party(branchId: $row['branchID'] ?? null, branchName: $row['branchName'] ?? null, locationId: $row['locationID'] ?? null),
            ],
            InventoryKind::ItemJournals => [
                $this->party(branchId: $row['branchID'] ?? null, branchName: $row['branchName'] ?? null, locationId: $row['locationID'] ?? null, locationName: $row['locationName'] ?? null),
                null,
            ],
            InventoryKind::SimpleTransfers => [
                $this->party(branchName: $row['originBranchName'] ?? null, locationName: $row['originLocationName'] ?? null),
                $this->party(branchName: $row['destinationBranchName'] ?? null, locationId: $row['destinationLocationID'] ?? null, locationName: $row['destinationLocationName'] ?? null),
            ],
        };

        return new InventoryDocumentSummary(
            provider: InventoryDocumentClient::PROVIDER,
            kind: $kind->value,
            number: $number,
            date: $this->nullableString($row[$this->dateField($kind)] ?? null),
            statusId: $this->nullableId($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
            from: $from,
            to: $to,
            transactionType: $this->nullableString($row['transType'] ?? null),
            reference: $reference,
            notes: $this->nullableString($row['additionalInfo'] ?? null),
            createdBy: $this->nullableString($row['createdBy'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no number.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(InventoryKind $kind, array $result): ?InventoryDocument
    {
        $number = $this->nullableString($result[$this->numberField($kind)] ?? null);

        if ($number === null) {
            return null;
        }

        $r = $result;

        [$from, $to] = match ($kind) {
            InventoryKind::GoodsTransferRequests => [
                $this->party(branchId: $r['originBranchID'] ?? null, branchName: $r['originBranchName'] ?? null, locationId: $r['originLocationID'] ?? null, locationName: $r['originLocationName'] ?? null),
                $this->party(branchId: $r['destinationBranchID'] ?? null, branchName: $r['destinationBranchName'] ?? null),
            ],
            InventoryKind::GoodsDeliveries => [
                $this->party(branchId: $r['originBranchID'] ?? null, branchName: $r['originBranch'] ?? null, locationId: $r['locationID'] ?? null, locationName: $r['locationName'] ?? null, address: $r['originBranchAddress'] ?? null, phone: $r['originBranchPhone'] ?? null),
                $this->party(branchId: $r['destinationBranchID'] ?? null, branchName: $r['destinationBranch'] ?? null, name: $r['customerInfo'] ?? null, address: $r['customerAddress'] ?? null, phone: $r['customerPhone'] ?? null),
            ],
            InventoryKind::GoodsReceipts => [
                $this->party(name: is_array($r['referenceModel'] ?? null) ? ($r['referenceModel']['sourceName'] ?? null) : null),
                $this->party(branchId: $r['branchID'] ?? null, branchName: $r['branchName'] ?? null, locationId: $r['locationID'] ?? null, locationName: $r['locationName'] ?? null),
            ],
            InventoryKind::ItemJournals => [
                $this->party(branchId: $r['branchID'] ?? null, branchName: $r['branchName'] ?? null, locationId: $r['locationID'] ?? null, locationName: $r['locationName'] ?? null),
                null,
            ],
            InventoryKind::SimpleTransfers => [
                $this->party(branchId: $r['originBranchID'] ?? null, branchName: $r['originBranchName'] ?? null, locationId: $r['originLocationID'] ?? null, locationName: $r['originLocationName'] ?? null),
                $this->party(branchId: $r['destinationBranchID'] ?? null, branchName: $r['destinationBranchName'] ?? null, locationId: $r['destinationLocationID'] ?? null, locationName: $r['destinationLocationName'] ?? null),
            ],
        };

        // Most documents stamp *Date; the transfer request stamps *At.
        $stamp = $kind === InventoryKind::GoodsTransferRequests ? 'At' : 'Date';

        return new InventoryDocument(
            provider: InventoryDocumentClient::PROVIDER,
            kind: $kind->value,
            number: $number,
            date: $this->nullableString($r[$this->dateField($kind)] ?? null),
            statusId: $this->nullableId($r['statusID'] ?? null),
            statusName: $this->nullableString($r['statusName'] ?? null),
            from: $from,
            to: $to,
            transactionType: $this->nullableString($r['transType'] ?? null),
            reference: $this->nullableString(match ($kind) {
                InventoryKind::GoodsDeliveries => $r['referenceNumber'] ?? null,
                InventoryKind::GoodsReceipts => $r['refNum'] ?? null,
                default => null,
            }),
            referenceDate: $this->nullableString(match ($kind) {
                InventoryKind::GoodsDeliveries => $r['refDate'] ?? null,
                InventoryKind::GoodsReceipts => is_array($r['referenceModel'] ?? null) ? ($r['referenceModel']['refDate'] ?? null) : null,
                default => null,
            }),
            notes: $this->nullableString($r['additionalInfo'] ?? null),
            costCenter: $this->nullableString($r['costCenterName'] ?? $r['costCenter'] ?? null),
            project: $this->nullableString($r['projectName'] ?? $r['project'] ?? null),
            lines: $this->lines($kind, $r),
            linkedDocuments: $this->linkedDocuments($kind, $r),
            attachmentCount: $this->attachmentCount($kind === InventoryKind::GoodsDeliveries ? ($r['goodsDeliveryURLAttachments'] ?? null) : ($r['imageAttachments'] ?? null)),
            createdBy: $this->nullableString($r['createdBy'] ?? null),
            createdAt: $this->nullableString($r['created'.$stamp] ?? null),
            editedBy: $this->nullableString($r['editedBy'] ?? null),
            editedAt: $this->nullableString($r['edited'.$stamp] ?? null),
            authorizedBy: $this->nullableString($r['authorizedBy'] ?? null),
            authorizedAt: $this->nullableString($r['authorized'.$stamp] ?? null),
            canEdit: $this->flag($r['canEdit'] ?? null),
            canDelete: $this->flag($r['canDelete'] ?? null),
            carryOver: $this->carryOver($kind, $r),
        );
    }

    /**
     * GET /inventory/goods-delivery/get-details-outstanding-qty rows as
     * productDetailId => qty.
     *
     * @param  array<mixed>  $rows
     * @return array<int, float>
     */
    public function outstandingFrom(array $rows): array
    {
        $outstanding = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $this->nullableId($row['productDetailID'] ?? null) : null;

            if ($id !== null && is_numeric($row['qty'] ?? null)) {
                $outstanding[$id] = (float) $row['qty'];
            }
        }

        return $outstanding;
    }

    /**
     * A stock-check result: true when the stock has been used since, null
     * when the answer is not a boolean.
     *
     * @param  array<mixed>  $result
     */
    public function stockUsedFrom(array $result): ?bool
    {
        $used = $result['isStockHasBeenUsed'] ?? null;

        return is_bool($used) ? $used : null;
    }

    private function numberField(InventoryKind $kind): string
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => 'transferNum',
            InventoryKind::GoodsDeliveries => 'goodsDeliveryNum',
            InventoryKind::GoodsReceipts => 'goodsReceiptNum',
            InventoryKind::ItemJournals => 'itemJournalNum',
            InventoryKind::SimpleTransfers => 'simpleTransferNum',
        };
    }

    private function dateField(InventoryKind $kind): string
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => 'transferDate',
            InventoryKind::GoodsDeliveries => 'goodsDeliveryDate',
            InventoryKind::GoodsReceipts => 'goodsReceiptDate',
            InventoryKind::ItemJournals => 'itemJournalDate',
            InventoryKind::SimpleTransfers => 'simpleTransferDate',
        };
    }

    /**
     * @param  array<mixed>  $result
     * @return list<InventoryLine>
     */
    private function lines(InventoryKind $kind, array $result): array
    {
        $field = match ($kind) {
            InventoryKind::GoodsTransferRequests => 'transferDetails',
            InventoryKind::GoodsDeliveries => 'goodsDeliveryDetails',
            InventoryKind::GoodsReceipts => 'goodsReceiptDetail',
            InventoryKind::ItemJournals => 'itemJournalDetails',
            InventoryKind::SimpleTransfers => 'simpleTransferDetails',
        };

        $lines = [];

        foreach (is_array($result[$field] ?? null) ? $result[$field] : [] as $line) {
            if (! is_array($line)) {
                continue;
            }

            $lines[] = new InventoryLine(
                productDetailId: $this->nullableId($line['productDetailID'] ?? null),
                productCode: $this->nullableString($line['productCode'] ?? null),
                productName: $this->nullableString($line['productName'] ?? null),
                unit: $this->nullableString($line['uomName'] ?? null),
                qty: $this->quantity($kind === InventoryKind::GoodsDeliveries ? ($line['deliveryQty'] ?? null) : ($line['qty'] ?? null)),
                requestedQty: $this->quantity($line['requestQty'] ?? null),
                availableQty: $this->quantity($line['availableQty'] ?? null),
                stockQty: $this->quantity($line['stockQty'] ?? null),
                pendingQty: $this->quantity($line['pendingQty'] ?? null),
                receivedQty: $this->quantity($line['receiptQty'] ?? null),
                cost: $this->quantity($line['hpp'] ?? null),
                notes: $this->nullableString($line['notes'] ?? null),
                purpose: $this->nullableString($line['purposeName'] ?? null),
                purposeId: $this->nullableId($line['purposeID'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * What an edit sends back unchanged ([ESB-035]).
     *
     * @param  array<mixed>  $result
     */
    private function carryOver(InventoryKind $kind, array $result): InventoryCarryOver
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => new InventoryCarryOver(
                purchaseRequestNumbers: array_map(
                    static fn (InventoryLinkedDocument $linked): string => $linked->number,
                    $this->linkedDocuments($kind, $result),
                ),
                // categoryTypeID 3 is an asset transfer request.
                hasAssets: $this->nonEmptyList($result['transferDetailAssets'] ?? null)
                    || $this->nullableId($result['categoryTypeID'] ?? null) === 3,
            ),
            InventoryKind::SimpleTransfers => new InventoryCarryOver(
                costCenterId: $this->nullableId($result['costCenterID'] ?? null),
                projectId: $this->nullableId($result['projectID'] ?? null),
                hasAssets: $this->nonEmptyList($result['simpleTransferAssets'] ?? null)
                    || $this->anyAssetLine($result['simpleTransferDetails'] ?? null),
            ),
            InventoryKind::ItemJournals => new InventoryCarryOver(
                requestTemplateId: $this->nullableId($result['requestTemplateID'] ?? null),
            ),
            default => new InventoryCarryOver,
        };
    }

    private function nonEmptyList(mixed $value): bool
    {
        return is_array($value) && array_filter($value, fn (mixed $item): bool => $item !== null && $item !== '' && $item !== []) !== [];
    }

    private function anyAssetLine(mixed $lines): bool
    {
        foreach (is_array($lines) ? $lines : [] as $line) {
            if (is_array($line) && ($this->flag($line['isAsset'] ?? null) === true || $this->nonEmptyList($line['assetIDs'] ?? null))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<mixed>  $result
     * @return list<InventoryLinkedDocument>
     */
    private function linkedDocuments(InventoryKind $kind, array $result): array
    {
        $linked = [];

        if ($kind === InventoryKind::GoodsTransferRequests) {
            foreach (is_array($result['purchaseRequestNum'] ?? null) ? $result['purchaseRequestNum'] : [] as $number) {
                if (($number = $this->nullableString($number)) !== null) {
                    $linked[] = new InventoryLinkedDocument('Purchase request', $number);
                }
            }
        }

        if ($kind === InventoryKind::GoodsReceipts) {
            foreach (['deliveryNum', 'linkGoodsDeliveryNum'] as $field) {
                $number = $this->nullableString($result[$field] ?? null);

                if ($number !== null && ! in_array($number, array_column($linked, 'number'), true)) {
                    $linked[] = new InventoryLinkedDocument('Goods delivery', $number);
                }
            }

            foreach (is_array($result['purchaseInvoice'] ?? null) ? $result['purchaseInvoice'] : [] as $invoice) {
                $number = is_array($invoice) ? $this->nullableString($invoice['purchaseInvoiceNum'] ?? null) : null;

                if ($number !== null) {
                    $linked[] = new InventoryLinkedDocument(
                        'Purchase invoice',
                        $number,
                        $this->nullableString($invoice['purchaseInvoiceDate'] ?? null),
                        $this->nullableString($invoice['statusName'] ?? null),
                    );
                }
            }
        }

        return $linked;
    }

    /**
     * Attachments come as URLs, or as objects with an imageUrl (goods
     * receipt); one without a URL is a placeholder.
     */
    private function attachmentCount(mixed $attachments): int
    {
        if (! is_array($attachments)) {
            return 0;
        }

        return count(array_filter($attachments, function (mixed $item): bool {
            $url = is_array($item) ? ($item['imageUrl'] ?? null) : $item;

            return is_string($url) && trim($url) !== '';
        }));
    }

    private function party(
        mixed $branchId = null,
        mixed $branchName = null,
        mixed $locationId = null,
        mixed $locationName = null,
        mixed $name = null,
        mixed $address = null,
        mixed $phone = null,
    ): ?InventoryParty {
        $party = new InventoryParty(
            branchId: $this->nullableId($branchId),
            branchName: $this->nullableString($branchName),
            locationId: $this->nullableId($locationId),
            locationName: $this->nullableString($locationName),
            name: $this->nullableString($name),
            address: $this->nullableString($address),
            phone: $this->nullableString($phone),
        );

        return $party->isEmpty() ? null : $party;
    }

    private function quantity(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
