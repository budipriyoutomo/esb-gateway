<?php

namespace App\Integrations\ESB\Mappers\Concerns;

/**
 * Defensive reads of ESB values (docs/10-security.md: responses are untrusted
 * input). The vendor's examples are zero-valued, so 0 and "" mean "none".
 *
 * Extracted for the master data mappers ([ESB-020]–[ESB-023]); the older
 * mappers still carry private copies of these helpers.
 */
trait ReadsEsbValues
{
    /**
     * A positive integer id, or null.
     */
    private function nullableId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    /**
     * A positive whole number (a count of days, months…), or null for 0,
     * negative or non-numeric.
     */
    private function positiveIntOrNull(mixed $value): ?int
    {
        return $this->nullableId($value);
    }

    private function stringOr(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * 1/0 (or a boolean). Anything else is unknown, not false.
     */
    private function flag(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match ($value) {
            1, '1' => true,
            0, '0' => false,
            default => null,
        };
    }
}
