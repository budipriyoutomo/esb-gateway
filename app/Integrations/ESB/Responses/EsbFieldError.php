<?php

namespace App\Integrations\ESB\Responses;

/**
 * One entry of an envelope's `errors[]`: which field (and, for a file
 * import, which row) the ESB refused, and why.
 *
 * The vendor documents `attribute`, `field`, `code`, `message` and `row` on
 * each entry. These messages are meant for whoever filled the form or the
 * sheet, unlike the envelope `message`, so they may be shown to the user --
 * but they are still untrusted input: fromArray() bounds and filters them.
 */
readonly class EsbFieldError
{
    /**
     * Longest message kept. The vendor's are one short sentence; anything
     * longer is not something a user needs to read in full.
     */
    public const MAX_MESSAGE_LENGTH = 300;

    public function __construct(
        public ?string $field,
        public ?int $row,
        public string $message,
    ) {}

    /**
     * @param  array<mixed>  $entry
     */
    public static function fromArray(array $entry): ?self
    {
        $message = is_string($entry['message'] ?? null) ? trim($entry['message']) : '';

        if ($message === '') {
            return null;
        }

        $field = $entry['field'] ?? null;

        if (! is_string($field) || $field === '') {
            $field = $entry['attribute'] ?? null;
        }

        $row = $entry['row'] ?? null;

        return new self(
            field: self::identifierOrNull($field),
            row: is_int($row) && $row > 0 ? $row : null,
            message: mb_strimwidth($message, 0, self::MAX_MESSAGE_LENGTH, '…'),
        );
    }

    /**
     * One of the FnB backend's plain-string refusals (Push Sales Data V2).
     *
     * The documented shapes are "Missing attribute: <path>", "<Thing> not
     * found: <path>" and "<path> cannot be blank". The field is the dotted
     * path the message names, or null when it names none.
     */
    public static function fromMessage(string $message): ?self
    {
        $message = trim($message);

        if ($message === '') {
            return null;
        }

        $field = null;

        if (preg_match('/:\s*(\S+)$/', $message, $matches) === 1) {
            $field = $matches[1];
        } elseif (preg_match('/^(\S+\.\S+)\s/', $message, $matches) === 1) {
            $field = $matches[1];
        }

        return new self(
            field: self::identifierOrNull($field),
            row: null,
            message: mb_strimwidth($message, 0, self::MAX_MESSAGE_LENGTH, '…'),
        );
    }

    /**
     * A field name is an identifier. Anything else is dropped rather than
     * echoed, so it cannot be used to smuggle markup to a page.
     */
    private static function identifierOrNull(mixed $field): ?string
    {
        return is_string($field) && preg_match('/^[A-Za-z0-9_.\[\]]{1,100}$/', $field) === 1 ? $field : null;
    }

    /**
     * @return array{row: int|null, field: string|null, message: string}
     */
    public function toArray(): array
    {
        return ['row' => $this->row, 'field' => $this->field, 'message' => $this->message];
    }
}
