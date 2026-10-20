<?php

namespace Tests\Feature\CoaAccounts;

use App\Authorization\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deactivating and restoring COA accounts ([ESB-025], S2). The vendor's
 * DELETE soft-deletes the account and its active descendants, and can be
 * restored, so the gateway calls it a deactivation.
 */
class CoaAccountWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'coa-accounts';
    }

    protected function upstreamPath(): string
    {
        return '/accounting/coa/1101';
    }

    protected function sampleId(): string
    {
        return '1101';
    }

    protected function prefix(): string
    {
        return 'coa';
    }

    protected function operations(): array
    {
        return [
            'deactivate' => ['PATCH', 'DELETE', ''],
            'restore' => ['PATCH', 'PATCH', '/restore'],
        ];
    }

    /**
     * Account numbers start with a digit; these would reach literal vendor
     * paths (/accounting/coa/export) or escape the segment.
     */
    protected function invalidIds(): array
    {
        return ['export', 'index', 'tax', rawurlencode('1/2'), '-1'];
    }

    #[Test]
    public function managing_accounts_needs_its_own_permission(): void
    {
        Http::fake();

        // Even an IT Admin, who manages connections and users, may not.
        $itAdmin = User::factory()->create();
        $itAdmin->assignRole(Role::ItAdmin->value);

        $this->send('deactivate', $itAdmin)->assertForbidden();
        Http::assertNothingSent();
    }
}
