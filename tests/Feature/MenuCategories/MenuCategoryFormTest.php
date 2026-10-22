<?php

namespace Tests\Feature\MenuCategories;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsMenuCategory;
use Tests\TestCase;

/**
 * Create / update of a POS menu category ([ESB-048], S3): esb.invoke +
 * menu-categories.manage, the vendor's sizes checked first, one attempt,
 * the ERP's EC0110 field errors back on the form, an unconfirmed write
 * audited. An update keeps every current detail.
 */
class MenuCategoryFormTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/';

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
        $user->assignRole(Role::IntegrationOperator->value);
        $user->givePermissionTo('menu-categories.manage');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return [
            'name' => 'Food-Int',
            'code' => 'FINT',
            'salesAccount' => 'Sales - Food',
            'cogsAccount' => 'COGS - Food',
            'discountAccount' => 'Food Discount',
            'description' => 'Makanan Cita Rasa Indonesia',
            'buttonColor' => '',
            'details' => [
                ['name' => 'Main Course-Int', 'code' => '', 'description' => '', 'maxOrderQty' => 1, 'buttonColor' => '#a4ff70'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function body(): array
    {
        return [
            'menuCategoryName' => 'Food-Int',
            'menuCategoryCode' => 'FINT',
            'salesAccount' => 'Sales - Food',
            'cogsAccount' => 'COGS - Food',
            'discountAccount' => 'Food Discount',
            'description' => 'Makanan Cita Rasa Indonesia',
            'themeCategoryOnPos' => '',
            'menuCategoryDetails' => [
                ['menuCategoryDetailName' => 'Main Course-Int', 'menuCategoryDetailCode' => '', 'description' => '', 'maxOrderQty' => 1, 'menuCategoryDetailTheme' => '#a4ff70'],
            ],
        ];
    }

    /**
     * The list on GET, $write on POST.
     *
     * @param  array<string, mixed>|null  $write
     */
    private function fakeUpstream(?array $write = null): void
    {
        Http::fake(fn (Request $request) => $request->method() === 'GET'
            ? Http::response(OmsMenuCategory::list([OmsMenuCategory::row()]))
            : Http::response($write ?? OmsMenuCategory::saved([OmsMenuCategory::row()])));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function store(array $input, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->post(route('menu-categories.store'), $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function update(array $input, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->put(route('menu-categories.update', OmsMenuCategory::ID), $input);
    }

    /**
     * @return array<string, mixed>
     */
    private function updateInput(): array
    {
        $input = $this->input();
        $input['details'][0]['id'] = 34;
        $input['details'][] = ['name' => 'Dessert-Int', 'code' => '', 'description' => '', 'maxOrderQty' => null, 'buttonColor' => ''];

        return [...$input, 'page' => 1];
    }

    #[Test]
    public function the_forms_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $withoutInvoke = User::factory()->create();
        $withoutInvoke->assignRole(Role::Viewer->value);
        $withoutInvoke->givePermissionTo('menu-categories.manage');

        foreach ([$operator, $withoutInvoke] as $user) {
            $this->actingAs($user)->get(route('menu-categories.create'))->assertForbidden();
            $this->actingAs($user)->get(route('menu-categories.edit', OmsMenuCategory::ID))->assertForbidden();
            $this->store($this->input(), $user)->assertForbidden();
            $this->update($this->updateInput(), $user)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_manager_can_create_a_category_sent_as_a_one_element_array(): void
    {
        $this->fakeUpstream();

        $manager = $this->manager();

        $this->store($this->input(), $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('menu-categories.show', OmsMenuCategory::ID))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE.'create-menu-category'
            && $request->data() === [$this->body()]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('menu-category.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(OmsMenuCategory::ID, $audit->metadata['id']);
        $this->assertSame('Food-Int', $audit->metadata['name']);
    }

    #[Test]
    public function the_vendors_sizes_are_checked_before_the_call(): void
    {
        Http::fake();

        $input = $this->input();
        [$detail] = $input['details'];

        $this->store([...$input, 'name' => ''])->assertSessionHasErrors('name');
        $this->store([...$input, 'name' => str_repeat('x', 51)])->assertSessionHasErrors('name');
        $this->store([...$input, 'salesAccount' => str_repeat('x', 21)])->assertSessionHasErrors('salesAccount');
        $this->store([...$input, 'cogsAccount' => null])->assertSessionHasErrors('cogsAccount');
        $this->store([...$input, 'buttonColor' => 'red'])->assertSessionHasErrors('buttonColor');
        $this->store([...$input, 'details' => []])->assertSessionHasErrors('details');
        $this->store([...$input, 'details' => [[...$detail, 'name' => '']]])->assertSessionHasErrors('details.0.name');
        $this->store([...$input, 'details' => [[...$detail, 'maxOrderQty' => -1]]])->assertSessionHasErrors('details.0.maxOrderQty');
        $this->store([...$input, 'details' => [[...$detail, 'buttonColor' => '#12']]])->assertSessionHasErrors('details.0.buttonColor');

        Http::assertNothingSent();
    }

    #[Test]
    public function the_erps_ec0110_field_errors_land_on_the_form(): void
    {
        $this->fakeUpstream(OmsMenuCategory::refused('{"salesAccount":["Sales    Test not found"],"menuCategoryDetailDesc":["Menu Category Detail Name cannot be blank."]}'));

        $this->store($this->input())->assertSessionHasErrors([
            'salesAccount' => 'Sales    Test not found',
            'details' => 'Menu Category Detail Name cannot be blank.',
        ]);

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unconfirmed_create_is_audited_once(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->store($this->input())
            ->assertRedirect(route('menu-categories.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $this->assertSame('menu-category.write-unconfirmed', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function the_edit_form_shows_the_category_from_its_page(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->manager())
            ->get(route('menu-categories.edit', ['id' => OmsMenuCategory::ID, 'page' => 1]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('menu-categories/form')
                ->where('category.id', OmsMenuCategory::ID)
                ->where('page', 1));
    }

    #[Test]
    public function a_manager_can_update_keeping_each_detail_and_adding_new_ones(): void
    {
        $this->fakeUpstream();

        $manager = $this->manager();

        $this->update($this->updateInput(), $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('menu-categories.show', OmsMenuCategory::ID))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $body = $this->body();
        $body['menuCategoryDetails'][0] = ['menuCategoryDetailID' => 34, ...$body['menuCategoryDetails'][0]];
        $body['menuCategoryDetails'][] = ['menuCategoryDetailID' => '', 'menuCategoryDetailName' => 'Dessert-Int', 'menuCategoryDetailCode' => '', 'description' => '', 'menuCategoryDetailTheme' => ''];

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE.'update-menu-category'
            && $request->data() === [['menuCategoryID' => OmsMenuCategory::ID, ...$body]]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('menu-category.updated', $audit->action);
        $this->assertSame(OmsMenuCategory::ID, $audit->metadata['id']);
    }

    #[Test]
    public function an_update_that_would_drop_a_detail_or_borrow_another_categorys_is_refused(): void
    {
        $this->fakeUpstream();

        $input = $this->updateInput();

        // Detail 34 left out.
        $this->update([...$input, 'details' => [$input['details'][1]]])->assertSessionHasErrors('details');
        // Detail 99 is not this category's.
        $this->update([...$input, 'details' => [...$input['details'], [...$input['details'][1], 'id' => 99]]])
            ->assertSessionHasErrors('details');

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_update_shares_the_categorys_write_lock(): void
    {
        $this->fakeUpstream();

        $lock = Cache::lock('menu-category-write:'.OmsMenuCategory::ID, 60);
        $lock->get();

        try {
            $this->update($this->updateInput())->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
    }
}
