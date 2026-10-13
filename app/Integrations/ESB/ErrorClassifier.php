<?php

namespace App\Integrations\ESB;

use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbUnknownException;
use App\Integrations\ESB\Responses\EsbEnvelope;
use App\Integrations\ESB\Responses\EsbFieldError;
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
    public const MAX_FIELD_ERRORS = 100;

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
            fieldErrors: $this->fieldErrors($envelope->errors)
                ?: $this->messageErrors($envelope->body['data'] ?? null),
        );
    }

    /**
     * The FnB backend's refusals: plain strings in `data` rather than objects
     * in `errors` (Push Sales Data V2, [ESB-011]). Bounded the same way.
     *
     * @return list<EsbFieldError>
     */
    private function messageErrors(mixed $messages): array
    {
        if (! is_array($messages) || ! array_is_list($messages)) {
            return [];
        }

        $fieldErrors = [];

        foreach ($messages as $message) {
            $fieldError = is_string($message) ? EsbFieldError::fromMessage($message) : null;

            if ($fieldError === null) {
                continue;
            }

            $fieldErrors[] = $fieldError;

            if (count($fieldErrors) === self::MAX_FIELD_ERRORS) {
                break;
            }
        }

        return $fieldErrors;
    }

    /**
     * The envelope's `errors[]`, bounded: a sheet with thousands of bad rows
     * must not become a response of thousands of messages.
     *
     * @return list<EsbFieldError>
     */
    private function fieldErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [];
        }

        $fieldErrors = [];

        foreach ($errors as $entry) {
            $fieldError = is_array($entry) ? EsbFieldError::fromArray($entry) : null;

            if ($fieldError === null) {
                continue;
            }

            $fieldErrors[] = $fieldError;

            if (count($fieldErrors) === self::MAX_FIELD_ERRORS) {
                break;
            }
        }

        return $fieldErrors;
    }
}
