<?php

namespace Tests\Feature\SalesPushes;

use App\Audit\AuditAction;
use App\Models\ApiClient;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * API clients are the POS systems allowed to call /api/v1 (ADR-022).
 *
 * They are issued and revoked from the console. The token is shown once and
 * only its SHA-256 is stored.
 */
class ApiClientCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function issuing_a_client_prints_the_token_once_and_stores_only_its_hash(): void
    {
        $this->artisan('api-client:create', ['name' => 'pos-rxt'])
            ->expectsOutputToContain('pos-rxt')
            ->assertSuccessful();

        $client = ApiClient::query()->where('name', 'pos-rxt')->sole();

        $this->assertTrue($client->is_active);
        $this->assertSame(64, strlen($client->token_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ApiClientIssued->value,
            'auditable_id' => $client->id,
        ]);
    }

    #[Test]
    public function a_client_is_scoped_to_sales_data_unless_told_otherwise(): void
    {
        $this->artisan('api-client:create', ['name' => 'pos-rxt'])->assertSuccessful();

        $client = ApiClient::query()->where('name', 'pos-rxt')->sole();
        $this->assertSame([ApiClient::SCOPE_SALES_DATA], $client->scopes());
        $this->assertTrue($client->allows(ApiClient::SCOPE_SALES_DATA));
        $this->assertFalse($client->allows(ApiClient::SCOPE_CLOSING_TOTALS));
    }

    #[Test]
    public function scopes_can_be_named_when_issuing(): void
    {
        $this->artisan('api-client:create', ['name' => 'sync-api', '--scope' => ['closing-totals']])
            ->expectsOutputToContain('closing-totals')
            ->assertSuccessful();

        $client = ApiClient::query()->where('name', 'sync-api')->sole();
        $this->assertSame([ApiClient::SCOPE_CLOSING_TOTALS], $client->scopes());

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ApiClientIssued->value,
            'auditable_id' => $client->id,
        ]);
        $this->assertSame(
            ['closing-totals'],
            AuditLog::query()->where('auditable_id', $client->id)->sole()->metadata['scopes'],
        );
    }

    #[Test]
    public function an_unknown_scope_is_refused(): void
    {
        $this->artisan('api-client:create', ['name' => 'sync-api', '--scope' => ['everything']])
            ->assertFailed();

        $this->assertSame(0, ApiClient::query()->count());
    }

    #[Test]
    public function a_client_from_before_scopes_may_push_sales_data_only(): void
    {
        [$client] = ApiClient::issue('pos-rxt');
        $client->forceFill(['scopes' => null])->save();

        $this->assertSame([ApiClient::SCOPE_SALES_DATA], $client->fresh()?->scopes());
    }

    #[Test]
    public function the_issued_token_authenticates_its_client(): void
    {
        [$client, $token] = ApiClient::issue('pos-rxt');

        $this->assertTrue(ApiClient::findActiveByToken($token)?->is($client));
        $this->assertNull(ApiClient::findActiveByToken($token.'x'));
        $this->assertNull(ApiClient::findActiveByToken(''));
    }

    #[Test]
    public function a_name_is_issued_only_once(): void
    {
        ApiClient::issue('pos-rxt');

        $this->artisan('api-client:create', ['name' => 'pos-rxt'])->assertFailed();

        $this->assertSame(1, ApiClient::query()->count());
    }

    #[Test]
    public function revoking_a_client_stops_its_token_working(): void
    {
        [$client, $token] = ApiClient::issue('pos-rxt');

        $this->artisan('api-client:revoke', ['name' => 'pos-rxt'])->assertSuccessful();

        $this->assertFalse($client->fresh()->is_active);
        $this->assertNull(ApiClient::findActiveByToken($token));
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::ApiClientRevoked->value)->count());
    }

    #[Test]
    public function revoking_an_unknown_client_fails(): void
    {
        $this->artisan('api-client:revoke', ['name' => 'nobody'])->assertFailed();
    }
}
