<?php

namespace App\Http\Controllers\Api;

use App\Actions\ClosingRecaps\ClosingReceipt;
use App\Actions\ClosingRecaps\ReceiveClosingRecap;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateApiClient;
use App\Http\Requests\Api\ReceiveClosingTotalsRequest;
use Illuminate\Http\JsonResponse;

/**
 * Closing recaps from sync-api Maharasa (ADR-032).
 *
 * The answers follow docs/spesifikasi-penerima-kirim-closing.md §6. The sender
 * treats every 409 as "already there, do not retry", so the body names which
 * of the two it is: only a duplicate means the data was stored.
 */
class ClosingTotalsController extends Controller
{
    public function store(ReceiveClosingTotalsRequest $request, ReceiveClosingRecap $receive): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->validated();

        $receipt = $receive->handle(
            AuthenticateApiClient::client($request),
            $request->outletCode(),
            $payload,
            (string) $request->getContent(),
        );

        return match ($receipt->outcome) {
            ClosingReceipt::DUPLICATE_MESSAGE => response()->json([
                'reason' => ClosingReceipt::DUPLICATE_MESSAGE,
                'message' => 'This messageId was already received.',
                'id' => $receipt->submission?->public_id,
            ], 409),

            ClosingReceipt::STALE_SEQUENCE => response()->json([
                'reason' => ClosingReceipt::STALE_SEQUENCE,
                'message' => 'A recap with this or a newer sequence is already held for this outlet and date. This one was not stored.',
                'currentSequence' => $receipt->currentSequence,
            ], 409),

            default => response()->json([
                'id' => $receipt->submission?->public_id,
                'recapId' => $receipt->recap?->public_id,
                'sequence' => $receipt->currentSequence,
                'items' => $receipt->items,
            ], 201),
        };
    }
}
