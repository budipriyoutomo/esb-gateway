<?php

namespace App\Actions\ClosingRecaps;

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use App\Models\ClosingSalesPush;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * Turn a closing recap into one ESB Push Sales Data V2 transaction (ADR-032,
 * P29). The prices, taxes and payments are sync-api's (contract revision 3,
 * TBD), forwarded as given: the gateway does not recompute tax. The salesNum
 * is fixed per outlet and date, so a revision overwrites the earlier one in
 * the ESB. Nothing is sent here.
 */
class PrepareClosingSalesPush
{
    /**
     * TBD (ADR-032 Open): the ESB needs a time of day; the recap has a date.
     * The end of the production day keeps the sale inside it.
     */
    public const SALES_TIME = '23:59:59';

    /** TBD: who the ESB records as having entered the sale. */
    public const CREATED_BY = 'CLOSING';

    /** TBD: the head and menu status ids, copied from the vendor's example (8 and 13). */
    public const HEAD_STATUS_ID = 8;

    public const MENU_STATUS_ID = 13;

    /**
     * @throws ClosingRecapNotReady
     */
    public function handle(ClosingRecap $recap, ?int $userId): ClosingSalesPush
    {
        $recap->load(['items' => fn ($query) => $query->orderBy('id')]);

        if (! $recap->hasSalesData()) {
            throw new ClosingRecapNotReady('sync-api sent no prices, taxes or payments for this recap (contract revision 3).');
        }

        $mappings = ClosingMappingSet::load();

        // Sales need the outlet and each sold item's menu; the item journal
        // mappings (BOMs, purposes) are not theirs to wait for.
        $gaps = array_values(array_filter(
            $mappings->missingFor($recap),
            fn (array $gap): bool => in_array($gap['type'], ['outlet', 'item-menu'], true),
        ));

        if ($gaps !== []) {
            throw new ClosingRecapNotReady($gaps[0]['message'].(count($gaps) > 1 ? ' (and '.(count($gaps) - 1).' more)' : ''));
        }

        $alreadySent = $recap->salesPushes()
            ->where('recap_sequence', $recap->sequence)
            ->where('status', ClosingSalesPush::STATUS_SENT)
            ->exists();

        if ($alreadySent) {
            throw new ClosingRecapNotReady('The sales of this version are already sent. A revision from sync-api can be prepared anew.');
        }

        $outlet = $mappings->outlet($recap->outlet_code);
        assert($outlet !== null); // checked above

        $date = $recap->production_date->format('Y-m-d');
        $salesNum = 'CL'.$outlet->branch_code.$recap->production_date->format('Ymd');
        $at = $date.' '.self::SALES_TIME;
        /** @var array<string, mixed> $sales */
        $sales = $recap->sales;

        $menu = [];

        foreach ($recap->items as $item) {
            $mapping = $mappings->item($item);

            if ($mapping === null || $mapping->esb_menu_id === null || Decimal::of($item->total_sold)->isZero()) {
                continue;
            }

            foreach ($item->prices ?? [] as $price) {
                $menu[] = [
                    'menuID' => $mapping->esb_menu_id,
                    'menuCode' => (string) $mapping->esb_menu_code,
                    'qty' => $price['qty'],
                    'originalPrice' => $price['originalPrice'],
                    'price' => $price['price'],
                    'discount' => $price['discount'],
                    'discountValue' => $price['discountValue'],
                    'otherTax' => $price['otherTax'],
                    'otherTaxValue' => $price['otherTaxValue'],
                    'vat' => $price['vat'],
                    'vatValue' => $price['vatValue'],
                    'otherVat' => $price['otherVat'],
                    'otherVatValue' => $price['otherVatValue'],
                    'otherTaxOnVat' => $price['otherTaxOnVat'],
                    'total' => $price['total'],
                    'notes' => $item->source === ClosingRecapItem::SOURCE_CLOSING ? '' : (string) $item->product_code,
                    'statusID' => self::MENU_STATUS_ID,
                    'promotionDetailID' => 0,
                    'promotionID' => '0',
                    'cancelNotes' => '',
                    'createdBy' => self::CREATED_BY,
                    'createdDate' => $at,
                    'editedDate' => $at,
                    'packages' => [],
                    'extras' => [],
                ];
            }
        }

        $head = [
            'salesNum' => $salesNum,
            'billNum' => $salesNum,
            'salesDate' => $date,
            'salesDateIn' => $at,
            'salesDateOut' => $at,
            'branchCode' => $outlet->branch_code,
            'memberCode' => '',
            'customerName' => '',
            'visitPurposeName' => (string) ($sales['visitPurposeName'] ?? ''),
        ];

        foreach (ClosingRecap::SALES_TOTALS as $field) {
            $head[$field] = $sales[$field] ?? 0;
        }

        $head += [
            'billingPrintCount' => 0,
            'paymentPrintCount' => 0,
            'additionalInfo' => sprintf('Closing %s %s, sequence %d', $recap->outlet_code, $date, $recap->sequence),
            'promotionID' => '0',
            'statusID' => self::HEAD_STATUS_ID,
            'createdBy' => self::CREATED_BY,
            'editedBy' => self::CREATED_BY,
            'editedDate' => $at,
            'menu' => $menu,
            'payment' => array_map(fn (array $payment): array => [
                'paymentMethod' => (string) $payment['paymentMethod'],
                'coaNo' => (string) ($payment['coaNo'] ?? ''),
                'voucherCode' => '',
                'notes' => (string) ($payment['notes'] ?? ''),
                'cardNumber' => '',
                'bankName' => '',
                'accountName' => '',
                'verificationCode' => '',
                'merchantDiscountRate' => $payment['merchantDiscountRate'] ?? 0,
                'paymentAmount' => $payment['paymentAmount'],
                'fullPaymentAmount' => $payment['fullPaymentAmount'],
            ], $recap->payments ?? []),
        ];

        return DB::transaction(function () use ($recap, $salesNum, $head, $userId): ClosingSalesPush {
            $recap->salesPushes()
                ->where('status', ClosingSalesPush::STATUS_PREPARED)
                ->update(['status' => ClosingSalesPush::STATUS_SUPERSEDED]);

            $draft = ClosingSalesPush::query()->create([
                'closing_recap_id' => $recap->id,
                'recap_sequence' => $recap->sequence,
                'sales_num' => $salesNum,
                'payload' => ['salesHead' => $head],
                'status' => ClosingSalesPush::STATUS_PREPARED,
                'prepared_by' => $userId,
            ]);

            AuditLog::record(
                action: AuditAction::ClosingSalesPrepared,
                auditable: $recap,
                metadata: [
                    'sequence' => $recap->sequence,
                    'sales_num' => $salesNum,
                    'menu_lines' => count($head['menu']),
                    'grand_total' => $head['grandTotal'],
                ],
            );

            return $draft;
        });
    }
}
