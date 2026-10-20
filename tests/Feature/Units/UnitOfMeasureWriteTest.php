<?php

namespace Tests\Feature\Units;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deleting and restoring units ([ESB-020], S2).
 */
class UnitOfMeasureWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'units';
    }

    protected function upstreamPath(): string
    {
        return '/units/4';
    }

    protected function sampleId(): int
    {
        return 4;
    }

    protected function prefix(): string
    {
        return 'unit';
    }

    protected function operations(): array
    {
        return [
            'delete' => ['DELETE', 'DELETE', ''],
            'restore' => ['PATCH', 'PATCH', '/restore'],
        ];
    }

    #[Test]
    public function whether_a_delete_was_permanent_is_audited_when_the_erp_says(): void
    {
        Http::fake(['*' => Http::response($this->envelope(['uomName' => 'KG', 'hardDelete' => false]))]);

        $this->send('delete');

        // The vendor: hardDelete tells whether the unit was removed or only
        // deactivated.
        $this->assertFalse(AuditLog::query()->sole()->metadata['hardDelete']);
    }

    #[Test]
    public function units_cannot_be_deactivated_here(): void
    {
        // The vendor documents no separate deactivate for units.
        $this->assertFalse(app('router')->has('units.deactivate'));
    }
}
