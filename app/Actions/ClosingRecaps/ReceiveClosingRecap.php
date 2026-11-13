<?php

namespace App\Actions\ClosingRecaps;

use App\Models\ApiClient;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use App\Models\ClosingSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hold one closing recap from sync-api Maharasa (ADR-032).
 *
 * Order matters: a known messageId is a duplicate before anything else; then
 * the recap row of the outlet and date is locked and its sequence compared.
 * A newer sequence replaces every item. Nothing here calls the ESB.
 */
class ReceiveClosingRecap
{
    /**
     * Two requests may create the same outlet/date row, or store the same
     * messageId, at once. The loser's insert fails on the unique key; it is
     * run again so it sees the winner's row, instead of answering 409 for data
     * that may be newer.
     */
    private const ATTEMPTS = 3;

    /**
     * @param  array<string, mixed>  $payload  Validated by ReceiveClosingTotalsRequest.
     */
    public function handle(ApiClient $client, string $outletCode, array $payload, string $body): ClosingReceipt
    {
        $hash = hash('sha256', $body);

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => $this->receive($client, $outletCode, $payload, $body, $hash));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function receive(ApiClient $client, string $outletCode, array $payload, string $body, string $hash): ClosingReceipt
    {
        $messageId = strtolower((string) $payload['messageId']);
        $sequence = (int) $payload['sequence'];
        $date = (string) $payload['productionDate'];

        $known = ClosingSubmission::query()->where('message_id', $messageId)->first();

        if ($known !== null) {
            if (! hash_equals($known->body_hash, $hash)) {
                throw ValidationException::withMessages([
                    'messageId' => 'This messageId was already received with a different body.',
                ]);
            }

            return ClosingReceipt::duplicate($known);
        }

        $recap = ClosingRecap::query()
            ->where('outlet_code', $outletCode)
            ->whereDate('production_date', $date)
            ->lockForUpdate()
            ->first();

        if ($recap !== null && $recap->sequence >= $sequence) {
            return ClosingReceipt::stale($recap->sequence);
        }

        $submission = ClosingSubmission::query()->create([
            'api_client_id' => $client->id,
            'message_id' => $messageId,
            'body_hash' => $hash,
            'outlet_code' => $outletCode,
            'production_date' => $date,
            'sequence' => $sequence,
            'sent_at' => CarbonImmutable::createFromFormat('Y-m-d\TH:i:s\Z', (string) $payload['sentAt'], 'UTC'),
            'body' => $body,
        ]);

        $recap ??= new ClosingRecap([
            'outlet_code' => $outletCode,
            'production_date' => $date,
            'status' => ClosingRecap::STATUS_RECEIVED,
        ]);

        if ($recap->exists && $recap->status !== ClosingRecap::STATUS_RECEIVED) {
            // Its documents were made from the version this one replaces.
            $recap->status = ClosingRecap::STATUS_REVISED;
        }

        $recap->fill([
            'sequence' => $sequence,
            'closing_submission_id' => $submission->id,
            'last_received_at' => now(),
            // Contract revision 3 (TBD): replaced with the version, like the items.
            'sales' => is_array($payload['sales'] ?? null) ? $payload['sales'] : null,
            'payments' => is_array($payload['payments'] ?? null) ? array_values($payload['payments']) : null,
        ])->save();

        /** @var list<array<string, mixed>> $items */
        $items = $payload['items'];

        $recap->items()->delete();
        $recap->items()->createMany(array_map($this->itemRow(...), $items));

        return ClosingReceipt::accepted($submission, $recap, count($items));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function itemRow(array $item): array
    {
        $isClosing = $item['source'] === ClosingRecapItem::SOURCE_CLOSING;
        $text = fn (string $key): ?string => isset($item[$key]) && $item[$key] !== '' ? (string) $item[$key] : null;

        $row = [
            'source' => $item['source'],
            'menu_id' => $isClosing && $text('menuId') !== null ? strtolower((string) $text('menuId')) : null,
            'menu_code' => $isClosing ? $text('menuCode') : null,
            'menu_name' => $isClosing ? $text('menuName') : null,
            'product_id' => $isClosing ? null : $text('productId'),
            'product_code' => $isClosing ? null : $text('productCode'),
            'product_name' => $isClosing ? null : $text('productName'),
        ];

        $row['prices'] = is_array($item['prices'] ?? null) ? array_values($item['prices']) : null;

        foreach (ClosingRecapItem::QUANTITIES as $field => $column) {
            // A float is turned back into its shortest decimal form, which is
            // what the sender wrote: at most six decimals, already validated.
            $row[$column] = (string) $item[$field];
        }

        return $row;
    }
}
