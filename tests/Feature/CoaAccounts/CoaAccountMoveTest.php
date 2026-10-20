<?php

namespace Tests\Feature\CoaAccounts;

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
use Tests\TestCase;

/**
 * Moving a COA account up or down among its siblings ([ESB-031],
 * PATCH /accounting/coa/reorder).
 */
class CoaAccountMoveTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://stg7.esb.co.id/core-int';

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

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    private function move(string $direction, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->manager())
            ->patch(route('coa-accounts.move', '1101'), ['direction' => $direction, 'name' => 'Kas']);
    }

    #[Test]
    public function moving_needs_coa_manage(): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);

        $this->move('up', $operator)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_move_is_sent_once_and_audited_with_its_direction(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return Http::response([
                'status' => 'ok', 'code' => 'EC03100000', 'message' => 'OK',
                'result' => ['coaNo' => '1101', 'type' => 'down'], 'errors' => null,
            ]);
        });

        $this->move('down')
            ->assertRedirect(route('coa-accounts.show', '1101'))
            ->assertSessionHas('inertia.flash_data.toast', ['type' => 'success', 'message' => 'Account Kas was moved.']);

        $this->assertSame([['PATCH '.self::BASE_URL.'/accounting/coa/reorder', ['coaNo' => '1101', 'type' => 'down']]], $sent);
        $audit = AuditLog::query()->sole();
        $this->assertSame('coa.moved', $audit->action);
        $this->assertSame(['provider' => 'esb', 'id' => '1101', 'direction' => 'down'], $audit->metadata);
    }

    #[Test]
    public function only_up_and_down_are_accepted(): void
    {
        Http::fake();

        $this->move('top')->assertSessionHasErrors('direction');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_timed_out_move_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->move('up')->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $audit = AuditLog::query()->sole();
        $this->assertSame('coa.write-unconfirmed', $audit->action);
        $this->assertSame('move', $audit->metadata['operation']);
    }
}
