<?php

namespace App\Domain\Canonical;

/**
 * What the ESB accepts in a document template's names ([ESB-016]), in one
 * place for the create form and the spreadsheet import (ADR-023).
 */
final class DocumentTemplateRules
{
    /**
     * The gateway's own bound on names; the vendor documents no length.
     */
    public const MAX_NAME_LENGTH = 255;

    /**
     * The vendor says "no emoji" without defining it. This covers the
     * pictographic blocks, dingbats, and the joiner and variation selector
     * that build emoji sequences.
     */
    public const EMOJI = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{200D}\x{FE0F}]/u';

    public static function hasEmoji(string $value): bool
    {
        return preg_match(self::EMOJI, $value) === 1;
    }
}
