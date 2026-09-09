<?php

namespace App\Integrations\ESB;

use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbUnknownException;
use App\Integrations\ESB\Responses\EsbEnvelope;
use Illuminate\Support\Facades\Log;

/**
 * Turns an unsuccessful ESB envelope into an application exception.
 *
 * Classification is on `code` alone -- never on `message` or `status`. The
 * vendor documentation carries 942 distinct code/message pairs for 29 codes,
 * messages interpolate document numbers, and `status` appears as both "fail"
 * and "failed". See docs/05-api-integration.md.
 */
class ErrorClassifier
{
    public function classify(EsbEnvelope $envelope, EsbContext $context): EsbException
    {
        $exceptionClass = ErrorCode::exceptionClassFor($envelope->code);

        if ($exceptionClass === null) {
            // Surface it. A code the vendor has added since our last contract
            // import should show up in the logs rather than being silently
            // folded into "unknown" forever.
            Log::warning('Unrecognised ESB status code', [
                ...$context->toLogContext(),
                'esb_code' => $envelope->code,
                'esb_message' => $envelope->message,
            ]);

            $exceptionClass = EsbUnknownException::class;
        }

        return new $exceptionClass(
            context: $context,
            esbCode: $envelope->code,
            upstreamMessage: $envelope->message,
        );
    }
}
