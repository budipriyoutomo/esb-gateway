<?php

namespace Tests\Feature\Pricelists;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CorePricelist;
use Tests\TestCase;

/**
 * Authorizing, rejecting and deleting a temporary pricelist ([ESB-027], S2).
 * Authorizing copies its rows into the master pricelist and removes it.
 */
class TempPricelistWriteTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = CorePricelist::TEMP_NUMBER;

    private const URL = 'https://stg7.esb.co.id/core-int/pricelist/temp/'.self::NUMBER;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function approver(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function ok(): array
    {
        return ['status' => 'ok', 'code' => 'EC03100000', 'message' => 'OK', 'result' => null, 'errors' => null];
    }

    #[Test]
    public function deciding_needs_the_approve_permission(): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);

        $this->actingAs($operator)
            ->post(route('pricelists.temp.decisions.store', self::NUMBER), ['decision' => 'authorize'])
            ->assertForbidden();
        $this->actingAs($operator)
            ->delete(route('pricelists.temp.destroy', self::NUMBER))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function authorizing_sends_one_patch_and_returns_to_the_list(): void
    {
        Http::fake(['*' => Http::response($this->ok())]);

        $this->actingAs($this->approver())
            ->post(route('pricelists.temp.decisions.store', self::NUMBER), ['decision' => 'authorize'])
            ->assertRedirect(route('pricelists.temp.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && $request->url() === self::URL.'/authorize');
        $this->assertSame('pricelist.authorized', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function rejecting_needs_no_reason_and_stays_on_the_pricelist(): void
    {
        Http::fake(['*' => Http::response($this->ok())]);

        $this->actingAs($this->approver())
            ->post(route('pricelists.temp.decisions.store', self::NUMBER), ['decision' => 'reject'])
            ->assertRedirect(route('pricelists.temp.show', self::NUMBER));

        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && $request->url() === self::URL.'/reject'
            && $request->body() === '');
        $this->assertSame('pricelist.rejected', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function an_unknown_decision_is_refused_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->approver())
            ->post(route('pricelists.temp.decisions.store', self::NUMBER), ['decision' => 'close'])
            ->assertSessionHasErrors('decision');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_timeout_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->approver())
            ->post(route('pricelists.temp.decisions.store', self::NUMBER), ['decision' => 'authorize'])
            ->assertRedirect(route('pricelists.temp.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $this->assertSame('pricelist.write-unconfirmed', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function deleting_sends_one_delete_and_returns_to_the_list(): void
    {
        Http::fake(['*' => Http::response($this->ok())]);

        $this->actingAs($this->approver())
            ->delete(route('pricelists.temp.destroy', self::NUMBER))
            ->assertRedirect(route('pricelists.temp.index'));

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === self::URL);
        $this->assertSame('pricelist.deleted', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function a_second_write_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();
        $lock = Cache::lock('pricelist-write:'.self::NUMBER, 60);
        $lock->get();

        try {
            $this->actingAs($this->approver())
                ->post(route('pricelists.temp.decisions.store', self::NUMBER), ['decision' => 'reject'])
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_number_that_could_reach_another_vendor_path_is_refused(): void
    {
        Http::fake();

        foreach (['export', 'abc'] as $number) {
            $this->actingAs($this->approver())
                ->post('/pricelists/temp/'.$number.'/decisions', ['decision' => 'authorize'])
                ->assertNotFound();
            $this->actingAs($this->approver())->delete('/pricelists/temp/'.$number)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
