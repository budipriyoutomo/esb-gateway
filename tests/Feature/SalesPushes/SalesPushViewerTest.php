<?php

namespace Tests\Feature\SalesPushes;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Jobs\PushSalesData;
use App\Models\ApiClient;
use App\Models\SalesPush;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\SalesDataV2;
use Tests\TestCase;

/**
 * The sales push history screen: what the POS sent, what the ESB said, and a
 * way to send a failed push again.
 */
class SalesPushViewerTest extends TestCase
{
    use RefreshDatabase;

    private ApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seed(RolePermissionSeeder::class);

        [$this->client] = ApiClient::issue('pos-rxt');
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $headOverrides
     */
    private function push(array $attributes = [], array $headOverrides = []): SalesPush
    {
        $push = SalesPush::receive($this->client, SalesDataV2::payload($headOverrides));
        $push->update($attributes);

        return $push;
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $push = $this->push();

        $this->get(route('sales-pushes.index'))->assertRedirect(route('login'));
        $this->get(route('sales-pushes.show', $push))->assertRedirect(route('login'));
        $this->post(route('sales-pushes.retry', $push))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_cannot_see_sales_pushes(): void
    {
        $viewer = $this->userWithRole(Role::Viewer);

        $this->actingAs($viewer)->get(route('sales-pushes.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('sales-pushes.show', $this->push()))->assertForbidden();
    }

    #[Test]
    public function an_operator_sees_the_history_newest_first(): void
    {
        $this->push(['status' => SalesPush::STATUS_SUCCEEDED], ['salesNum' => 'S-OLD']);
        $this->push([], ['salesNum' => 'S-NEW']);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('sales-pushes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sales-pushes/index')
                ->has('pushes.data', 2)
                ->where('pushes.data.0.salesNum', 'S-NEW')
                ->where('pushes.data.0.client', 'pos-rxt')
                ->where('pushes.data.1.status', SalesPush::STATUS_SUCCEEDED)
                ->missing('pushes.data.0.payload'));
    }

    #[Test]
    public function the_history_can_be_filtered(): void
    {
        $this->push(['status' => SalesPush::STATUS_REJECTED], ['salesNum' => 'S-1', 'branchCode' => 'RXT']);
        $this->push([], ['salesNum' => 'S-2', 'branchCode' => 'ABC']);

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->get(route('sales-pushes.index', ['status' => 'rejected']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('pushes.data', 1)
                ->where('pushes.data.0.salesNum', 'S-1'));

        $this->actingAs($operator)
            ->get(route('sales-pushes.index', ['salesNum' => 'S-2']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('pushes.data', 1));

        $this->actingAs($operator)
            ->get(route('sales-pushes.index', ['branchCode' => 'ABC']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('pushes.data', 1));

        $this->actingAs($operator)
            ->get(route('sales-pushes.index', ['status' => 'bogus']))
            ->assertSessionHasErrors('status');
    }

    #[Test]
    public function the_detail_shows_a_summary_but_never_payment_card_details(): void
    {
        $push = $this->push(
            ['status' => SalesPush::STATUS_REJECTED, 'errors' => [['field' => 'salesHead.memberCode', 'message' => 'Member not found: salesHead.memberCode']]],
            ['payment' => [[...SalesDataV2::payload()['salesHead']['payment'][0], 'cardNumber' => '4111111111111111']]],
        );

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('sales-pushes.show', $push))
            ->assertOk()
            ->assertDontSee('4111111111111111')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sales-pushes/show')
                ->where('push.salesNum', 'SRXT172777428297')
                ->where('push.errors.0.field', 'salesHead.memberCode')
                ->where('push.summary.grandTotal', 69000)
                ->where('push.summary.menuLines', 1)
                ->where('push.summary.payments', 1)
                ->where('push.canRetry', true)
                ->missing('push.payload'));
    }

    #[Test]
    public function reading_is_not_enough_to_retry(): void
    {
        $push = $this->push(['status' => SalesPush::STATUS_FAILED]);

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->post(route('sales-pushes.retry', $push))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function an_operator_can_retry_a_failed_push(): void
    {
        $push = $this->push(['status' => SalesPush::STATUS_FAILED]);
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->post(route('sales-pushes.retry', $push))
            ->assertRedirect(route('sales-pushes.show', $push))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame(SalesPush::STATUS_QUEUED, $push->fresh()->status);
        Queue::assertPushed(PushSalesData::class, fn (PushSalesData $job) => $job->salesPushId === $push->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SalesPushRetried->value,
            'actor_id' => $operator->id,
            'auditable_id' => $push->id,
        ]);
    }

    #[Test]
    public function a_push_that_is_queued_or_succeeded_cannot_be_retried(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        foreach ([SalesPush::STATUS_QUEUED, SalesPush::STATUS_SUCCEEDED, SalesPush::STATUS_SUPERSEDED] as $status) {
            $push = $this->push(['status' => $status]);

            $this->actingAs($operator)
                ->post(route('sales-pushes.retry', $push))
                ->assertRedirect(route('sales-pushes.show', $push))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

            $this->assertSame($status, $push->fresh()->status);
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function an_older_push_cannot_be_retried_over_a_newer_one(): void
    {
        $older = $this->push(['status' => SalesPush::STATUS_FAILED]);
        $this->push(['status' => SalesPush::STATUS_SUCCEEDED]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('sales-pushes.retry', $older))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(SalesPush::STATUS_FAILED, $older->fresh()->status);
        Queue::assertNothingPushed();
    }
}
