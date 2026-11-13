<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Exact decimal quantities as strings, for closing recaps (ADR-032). Floats
 * appear only at the ESB boundary, where the vendor's JSON wants numbers.
 */
final class Decimal
{
    /**
     * Decimals an item journal quantity is rounded to. TBD: the vendor
     * documents no precision for item journals; a simple transfer takes at
     * most four (docs/05-api-integration.md, [ESB-035]).
     */
    public const JOURNAL_SCALE = 4;

    public static function of(int|float|string|BigDecimal $value): BigDecimal
    {
        return $value instanceof BigDecimal ? $value : BigDecimal::of(is_string($value) ? $value : (string) $value);
    }

    /**
     * Without trailing zeros: "13.000000" -> "13", "-1.50" -> "-1.5".
     */
    public static function trim(int|float|string|BigDecimal $value): string
    {
        $text = (string) self::of($value);

        if (! str_contains($text, '.')) {
            return $text;
        }

        $text = rtrim(rtrim($text, '0'), '.');

        return $text === '-0' ? '0' : $text;
    }

    /**
     * @param  int<0, max>  $scale
     */
    public static function round(BigDecimal $value, int $scale = self::JOURNAL_SCALE): string
    {
        return self::trim($value->toScale($scale, RoundingMode::HalfUp));
    }

    /**
     * The JSON number for the ESB: an integer when there is no fraction.
     */
    public static function toNumber(string $value): int|float
    {
        return str_contains($value, '.') ? (float) $value : (int) $value;
    }
}
