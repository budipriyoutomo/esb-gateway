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
                ?: $this->messageErrors($envelope->body['data'] ?? null)
                ?: $this->keyedErrors($envelope->body['data'] ?? null)
                ?: $this->omsMasterErrors($envelope),
        );
    }

    /**
     * An ESB OMS master refusal (EC0110, [ESB-048]) puts its per-field
     * errors as a JSON object in `message`; when the message is not one, it
     * is the refusal itself and is kept without a field. Only EC0110 is read
     * this way: any other code's message stays diagnostic.
     *
     * @return list<EsbFieldError>
     */
    private function omsMasterErrors(EsbEnvelope $envelope): array
    {
        if ($envelope->code !== ErrorCode::OMS_VALIDATION || $envelope->message === null) {
            return [];
        }

        $decoded = json_decode($envelope->message, true);

        if (is_array($decoded) && ! array_is_list($decoded)) {
            return $this->keyedErrors($decoded);
        }

        $fieldError = EsbFieldError::forField(null, $envelope->message);

        return $fieldError === null ? [] : [$fieldError];
    }

    /**
     * Errors keyed by field, each a message or a list of them: the OMS
     * promotion create's `data` ([ESB-050]) and the EC0110 message. A key
     * that is not an identifier keeps its message without a field.
     *
     * @return list<EsbFieldError>
     */
    private function keyedErrors(mixed $errors): array
    {
        if (! is_array($errors) || array_is_list($errors)) {
            return [];
        }

        $fieldErrors = [];

        foreach ($errors as $field => $messages) {
            foreach (is_array($messages) ? $messages : [$messages] as $message) {
                $fieldError = is_string($message) ? EsbFieldError::forField((string) $field, $message) : null;

                if ($fieldError === null) {
                    continue;
                }

                $fieldErrors[] = $fieldError;

                if (count($fieldErrors) === self::MAX_FIELD_ERRORS) {
                    return $fieldErrors;
                }
            }
        }

        return $fieldErrors;
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
