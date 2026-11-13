<?php

namespace App\Actions\ClosingRecaps;

use App\Models\ClosingRecap;
use App\Models\ClosingSubmission;

/**
 * What became of one closing recap request (ADR-032).
 */
final readonly class ClosingReceipt
{
    public const ACCEPTED = 'accepted';

    /** The messageId was already stored: the data is here. */
    public const DUPLICATE_MESSAGE = 'duplicate_message';

    /** A sequence at or above this one is already held: this one is NOT stored. */
    public const STALE_SEQUENCE = 'stale_sequence';

    private function __construct(
        public string $outcome,
        public ?ClosingSubmission $submission,
        public ?ClosingRecap $recap,
        public ?int $currentSequence = null,
        public int $items = 0,
    ) {}

    public static function accepted(ClosingSubmission $submission, ClosingRecap $recap, int $items): self
    {
        return new self(self::ACCEPTED, $submission, $recap, $recap->sequence, $items);
    }

    public static function duplicate(ClosingSubmission $submission): self
    {
        return new self(self::DUPLICATE_MESSAGE, $submission, null);
    }

    public static function stale(int $currentSequence): self
    {
        return new self(self::STALE_SEQUENCE, null, null, $currentSequence);
    }
}
