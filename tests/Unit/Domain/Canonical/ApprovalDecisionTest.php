<?php

namespace Tests\Unit\Domain\Canonical;

use App\Domain\Canonical\ApprovalDecision;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The one approval vocabulary shared by every approvable document.
 *
 * The values are what the decision forms submit, so changing them breaks
 * the pages; the ESB adapters map them onto their own path segments.
 */
class ApprovalDecisionTest extends TestCase
{
    #[Test]
    public function it_has_exactly_authorize_and_reject(): void
    {
        $this->assertSame(
            ['authorize', 'reject'],
            array_map(fn (ApprovalDecision $d) => $d->value, ApprovalDecision::cases()),
        );
    }

    #[Test]
    public function it_parses_the_submitted_value(): void
    {
        $this->assertSame(ApprovalDecision::Authorize, ApprovalDecision::from('authorize'));
        $this->assertSame(ApprovalDecision::Reject, ApprovalDecision::from('reject'));
        $this->assertNull(ApprovalDecision::tryFrom('approve'));
    }
}
