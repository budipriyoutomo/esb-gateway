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
 * Turn a closing recap into ESB Push Sales Data V2 transactions (ADR-032,
 * P29-P30): one per visit purpose, as sync-api splits its sales (contract
 * revision 3, TBD). The figures are sync-api's, forwarded as given: the
 * gateway does not recompute tax. Each payment's ERP account and merchant
 * discount rate come from the gateway's payment method mapping.
 *
 * The salesNum is fixed per outlet, date and visit purpose, so a revision
 * overwrites the earlier transaction in the ESB. Nothing is sent here.
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
     * The salesNum ends in one digit per visit purpose: CL + branch code (at
     * most 10) + YYMMDD + slot is at most 19 of the ESB's 20 characters.
     */
    public const MAX_SLOT = 9;

    /**
     * @return list<ClosingSalesPush> The new drafts, in the order of the sales.
     *
     * @throws ClosingRecapNotReady
     */
    public function handle(ClosingRecap $recap, ?int $userId): array
    {
        $recap->load(['items' => fn ($query) => $query->orderBy('id')]);

        if (! $recap->hasSalesData()) {
            throw new ClosingRecapNotReady('sync-api sent no sales for this recap (contract revision 3).');
        }

        $mappings = ClosingMappingSet::load();
        $gaps = $mappings->salesMissingFor($recap);

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

        /** @var list<array<string, mixed>> $sales */
        $sales = $recap->sales;
        $slots = $this->slots($recap, $sales);

        $drafts = [];

        foreach ($sales as $element) {
            $purpose = (string) $element['visitPurposeName'];
            $slot = $slots[ClosingRecap::purposeKey($purpose)];
            $salesNum = 'CL'.$outlet->branch_code.$recap->production_date->format('ymd').$slot;

            $drafts[] = [
                'visit_purpose_name' => $purpose,
                'slot' => $slot,
                'sales_num' => $salesNum,
                'head' => $this->head($recap, $mappings, $outlet->branch_code, $salesNum, $element),
            ];
        }

        return DB::transaction(function () use ($recap, $drafts, $userId): array {
            $recap->salesPushes()
                ->where('status', ClosingSalesPush::STATUS_PREPARED)
                ->update(['status' => ClosingSalesPush::STATUS_SUPERSEDED]);

            $created = array_map(fn (array $draft): ClosingSalesPush => ClosingSalesPush::query()->create([
                'closing_recap_id' => $recap->id,
                'recap_sequence' => $recap->sequence,
                'visit_purpose_name' => $draft['visit_purpose_name'],
                'slot' => $draft['slot'],
                'sales_num' => $draft['sales_num'],
                'payload' => ['salesHead' => $draft['head']],
                'status' => ClosingSalesPush::STATUS_PREPARED,
                'prepared_by' => $userId,
            ]), $drafts);

            AuditLog::record(
                action: AuditAction::ClosingSalesPrepared,
                auditable: $recap,
                metadata: [
                    'sequence' => $recap->sequence,
                    'transactions' => array_map(fn (array $draft): array => [
                        'visit_purpose' => $draft['visit_purpose_name'],
                        'sales_num' => $draft['sales_num'],
                        'menu_lines' => count($draft['head']['menu']),
                        'grand_total' => $draft['head']['grandTotal'],
                    ], $drafts),
                ],
            );

            return $created;
        });
    }

    /**
     * Each visit purpose's slot: the one it had in an earlier preparation of
     * this recap, or the next one no purpose has used. A purpose gone from a
     * revision keeps its slot, because the ESB still holds its transaction.
     *
     * @param  list<array<string, mixed>>  $sales
     * @return array<string, int> By ClosingRecap::purposeKey().
     *
     * @throws ClosingRecapNotReady
     */
    private function slots(ClosingRecap $recap, array $sales): array
    {
        $slots = [];

        foreach ($recap->salesPushes()->orderBy('id')->get(['visit_purpose_name', 'slot']) as $earlier) {
            $slots[ClosingRecap::purposeKey($earlier->visit_purpose_name)] ??= $earlier->slot;
        }

        foreach ($sales as $element) {
            $key = ClosingRecap::purposeKey((string) $element['visitPurposeName']);

            if (! isset($slots[$key])) {
                $next = $slots === [] ? 1 : max($slots) + 1;

                if ($next > self::MAX_SLOT) {
                    throw new ClosingRecapNotReady(sprintf('This recap has had more than %d visit purposes; their sales numbers would not fit.', self::MAX_SLOT));
                }

                $slots[$key] = $next;
            }
        }

        return $slots;
    }

    /**
     * @param  array<string, mixed>  $sales  One visit purpose.
     * @return array<string, mixed>
     */
    private function head(ClosingRecap $recap, ClosingMappingSet $mappings, string $branchCode, string $salesNum, array $sales): array
    {
        $date = $recap->production_date->format('Y-m-d');
        $at = $date.' '.self::SALES_TIME;
        $purpose = (string) $sales['visitPurposeName'];
        $purposeKey = ClosingRecap::purposeKey($purpose);

        $menu = [];

        foreach ($recap->items as $item) {
            $mapping = $mappings->item($item);

            if ($mapping === null || $mapping->esb_menu_id === null || Decimal::of($item->total_sold)->isZero()) {
                continue;
            }

            foreach ($item->prices ?? [] as $price) {
                if (ClosingRecap::purposeKey((string) ($price['visitPurposeName'] ?? '')) !== $purposeKey) {
                    continue;
                }

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
            'branchCode' => $branchCode,
            'memberCode' => '',
            'customerName' => '',
            'visitPurposeName' => $purpose,
        ];

        foreach (ClosingRecap::SALES_TOTALS as $field) {
            $head[$field] = $sales[$field] ?? 0;
        }

        return $head + [
            'billingPrintCount' => 0,
            'paymentPrintCount' => 0,
            'additionalInfo' => sprintf('Closing %s %s %s, sequence %d', $recap->outlet_code, $date, $purpose, $recap->sequence),
            'promotionID' => '0',
            'statusID' => self::HEAD_STATUS_ID,
            'createdBy' => self::CREATED_BY,
            'editedBy' => self::CREATED_BY,
            'editedDate' => $at,
            'menu' => $menu,
            'payment' => array_map(function (array $payment) use ($mappings): array {
                $account = $mappings->payment((string) $payment['paymentMethod']);
                assert($account !== null); // salesMissingFor() checked it.

                return [
                    'paymentMethod' => (string) $payment['paymentMethod'],
                    'coaNo' => $account->coa_no,
                    'voucherCode' => '',
                    'notes' => (string) ($payment['notes'] ?? ''),
                    'cardNumber' => '',
                    'bankName' => '',
                    'accountName' => '',
                    'verificationCode' => '',
                    'merchantDiscountRate' => Decimal::toNumber(Decimal::trim($account->merchant_discount_rate)),
                    'paymentAmount' => $payment['paymentAmount'],
                    'fullPaymentAmount' => $payment['fullPaymentAmount'],
                ];
            }, $sales['payments'] ?? []),
        ];
    }
}
