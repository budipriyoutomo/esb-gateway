<?php

namespace Tests\Feature\Promotions;

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsPromotion;
use Tests\TestCase;

/**
 * Create of a POS promotion ([ESB-050], S3): five documented bodies on one
 * path. Each type sends its own fields and never another type's; booleans
 * go as "Yes" / "No"; one attempt; the ERP's field errors (keyed in `data`)
 * back on the form.
 */
class PromotionFormTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/promotion/';

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
        $user->givePermissionTo('promotions.manage');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private static function common(int $type): array
    {
        return [
            'type' => $type,
            'branchCodes' => ['LR00'],
            'notes' => 'Promo',
            'promotionCode' => 'PR1',
            'description' => 'Promo desc',
            'days' => [1, 2],
            'startAt' => '2026-11-01 00:00:00',
            'endAt' => '2026-11-30 23:59:00',
            'allCategories' => false,
            'applyDiscountTo' => 1,
            'menuCategoryIds' => [16],
            // Belongs to the ESO types only; must never reach the others.
            'showOnEso' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function commonBody(int $type): array
    {
        return [
            'branchCode' => ['LR00'],
            'promotionType' => $type,
            'notes' => 'Promo',
            'promotionDaysID' => [1, 2],
            'startDate' => '2026-11-01 00:00:00',
            'endDate' => '2026-11-30 23:59:00',
            'allCategories' => 'No',
            'applyDiscountTo' => 1,
            'menuCategoryID' => [16],
            'menuCategoryDetailID' => [],
            'menuID' => [],
            'promotionCode' => 'PR1',
            'promotionDesc' => 'Promo desc',
        ];
    }

    /**
     * @return array<string, array{int, array<string, mixed>, array<string, mixed>}>
     */
    public static function types(): array
    {
        $percentage = [
            'discount' => 10,
            'authorizationNeeded' => false,
            'minSalesPrice' => 20000,
            'maxSalesPrice' => 50000,
            'applyTo' => 'Staff Only',
            'employeeGroups' => ['SAE Group'],
            'includePackageContent' => true,
            'includeMenuExtra' => false,
            'times' => [['start' => '07:00:00', 'end' => '10:00:00']],
        ];
        $percentageBody = [
            'discount' => 10,
            'authorizationNeeded' => 'No',
            'minSalesPrice' => 20000,
            'maxSalesPrice' => 50000,
            'applyTo' => 'Staff Only',
            'employeeGroupName' => ['SAE Group'],
            'includePackageContent' => 'Yes',
            'includeMenuExtra' => 'No',
            'promotionTime' => [['startTime' => '07:00:00', 'endTime' => '10:00:00']],
        ];

        return [
            'discount (%)' => [1, $percentage, $percentageBody],
            'discount limit (%)' => [10, $percentage, $percentageBody],
            'discount (%) ESO' => [5, [
                'discount' => 10,
                'minSalesPrice' => 0,
                'maxDiscount' => 50000,
                'showOnEso' => true,
                'maxUsage' => 1,
                'maxUsageTotal' => 11,
                'visitPurposeIds' => [2],
                'selfOrderPaymentMethodCodes' => ['creditcard', 'cc88'],
                'bankIdentificationNumbers' => ['123456'],
                // A percentage type's field: never sent for ESO.
                'includeMenuExtra' => true,
            ], [
                'discount' => 10,
                'minSalesPrice' => 0,
                'maxDiscount' => 50000,
                'showPromotionEzo' => 'Yes',
                'maxUsage' => 1,
                'maxUsageTotal' => 11,
                'visitPurposeID' => [2],
                'selfOrderPaymentMethodCode' => ['creditcard', 'cc88'],
                'bankIdentificationNumbers' => ['123456'],
            ]],
            'discount (Rp) ESO' => [6, [
                'discount' => 10000,
                'minSalesPrice' => 0,
                'showOnEso' => false,
                'maxUsage' => 1,
                'maxUsageTotal' => 11,
                'selfOrderPaymentMethodCodes' => ['ovo'],
            ], [
                'discount' => 10000,
                'minSalesPrice' => 0,
                'showPromotionEzo' => 'No',
                'maxUsage' => 1,
                'maxUsageTotal' => 11,
                'visitPurposeID' => [],
                'selfOrderPaymentMethodCode' => ['ovo'],
                'bankIdentificationNumbers' => [],
            ]],
            'free item' => [4, [
                'authorizationNeeded' => false,
                'applications' => ['pos', 'eso'],
                'selfOrderPaymentMethodCodes' => ['cc88'],
                'maxUsage' => 1,
                'maxUsageTotal' => 11,
                'voucherSource' => 'Giftee',
                'minSalesPrice' => 10000,
                'prefix' => '12345',
                'discount' => 99,
            ], [
                'authorizationNeeded' => 'No',
                'employeeGroupName' => [],
                'applyToApplicationID' => ['pos', 'eso'],
                'selfOrderPaymentMethodCode' => ['cc88'],
                'maxUsage' => 1,
                'maxUsageTotal' => 11,
                'visitPurposeID' => [],
                'promotionTime' => [],
                'voucherSourceName' => 'Giftee',
                'minSalesPrice' => 10000,
                'prefixPromotion' => '12345',
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function store(array $input, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->post(route('promotions.store'), $input);
    }

    #[Test]
    public function creating_needs_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $withoutInvoke = User::factory()->create();
        $withoutInvoke->assignRole(Role::Viewer->value);
        $withoutInvoke->givePermissionTo('promotions.manage');

        foreach ([$operator, $withoutInvoke] as $user) {
            $this->actingAs($user)->get(route('promotions.create'))->assertForbidden();
            $this->store(self::common(1), $user)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_form_offers_the_five_documented_types(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route('promotions.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('promotions/form')
                ->where('types.0', ['id' => 1, 'name' => 'Discount (%)', 'fields' => ['discount', 'authorizationNeeded', 'minSalesPrice', 'maxSalesPrice', 'applyTo', 'employeeGroups', 'includePackageContent', 'includeMenuExtra', 'times']])
                ->has('types', 5));

        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $body
     */
    #[Test]
    #[DataProvider('types')]
    public function each_type_sends_its_own_body_once(int $type, array $input, array $body): void
    {
        Http::fake(['*' => Http::response(OmsPromotion::saved(119, 'Promo'))]);

        $manager = $this->manager();

        $this->store([...self::common($type), ...$input], $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::URL
            && $request->data() === [...self::commonBody($type), ...$body]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('promotion.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(119, $audit->metadata['id']);
        $this->assertSame($type, $audit->metadata['type']);
        $this->assertSame('PR1', $audit->metadata['code']);
    }

    #[Test]
    public function a_promotion_for_all_categories_sends_no_scope(): void
    {
        Http::fake(['*' => Http::response(OmsPromotion::saved())]);

        $input = [...self::common(6), 'allCategories' => true, 'discount' => 1, 'minSalesPrice' => 0, 'maxUsage' => 1, 'maxUsageTotal' => 1, 'selfOrderPaymentMethodCodes' => ['ovo'], 'usedForLoyalty' => true];

        $this->store($input)->assertSessionHasNoErrors();

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return $data['allCategories'] === 'Yes'
                && ! array_key_exists('applyDiscountTo', $data)
                && $data['menuCategoryID'] === []
                && $data['usedForLoyalty'] === 'Yes';
        });
    }

    #[Test]
    public function a_free_item_for_pos_only_sends_no_eso_fields_and_needs_no_prefix_from_esb(): void
    {
        Http::fake(['*' => Http::response(OmsPromotion::saved())]);

        $input = [...self::common(4), 'authorizationNeeded' => true, 'applications' => ['pos'], 'voucherSource' => 'ESB', 'minSalesPrice' => 0, 'maxUsage' => 5, 'prefix' => 'X'];

        $this->store($input)->assertSessionHasNoErrors();

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return ! array_key_exists('selfOrderPaymentMethodCode', $data)
                && ! array_key_exists('maxUsage', $data)
                && ! array_key_exists('prefixPromotion', $data)
                && $data['voucherSourceName'] === 'ESB';
        });
    }

    #[Test]
    public function the_vendors_rules_are_checked_before_the_call(): void
    {
        Http::fake();

        $percentage = [...self::common(1), ...self::types()['discount (%)'][1]];
        $eso = [...self::common(5), ...self::types()['discount (%) ESO'][1]];
        $free = [...self::common(4), ...self::types()['free item'][1]];

        $this->store([...$percentage, 'type' => 2])->assertSessionHasErrors('type');
        $this->store([...$percentage, 'branchCodes' => []])->assertSessionHasErrors('branchCodes');
        $this->store([...$percentage, 'notes' => str_repeat('x', 101)])->assertSessionHasErrors('notes');
        $this->store([...$percentage, 'days' => [8]])->assertSessionHasErrors('days.0');
        $this->store([...$percentage, 'endAt' => '2026-10-01 00:00:00'])->assertSessionHasErrors('endAt');
        $this->store([...$percentage, 'startAt' => '2026-11-01'])->assertSessionHasErrors('startAt');
        $this->store([...$percentage, 'discount' => 101])->assertSessionHasErrors('discount');
        $this->store([...$percentage, 'applyDiscountTo' => 2])->assertSessionHasErrors('menuCategoryDetailIds');
        $this->store([...$percentage, 'applyTo' => 'Everyone'])->assertSessionHasErrors('applyTo');
        $this->store([...$percentage, 'applyTo' => 'Member Only'])->assertSessionHasErrors('employeeGroups');
        $this->store([...$percentage, 'times' => [['start' => '10:00:00', 'end' => '07:00:00']]])->assertSessionHasErrors('times.0.end');
        $this->store([...$eso, 'selfOrderPaymentMethodCodes' => []])->assertSessionHasErrors('selfOrderPaymentMethodCodes');
        $this->store([...$eso, 'promotionCode' => ''])->assertSessionHasErrors('promotionCode');
        $this->store([...$eso, 'bankIdentificationNumbers' => ['12345']])->assertSessionHasErrors('bankIdentificationNumbers.0');
        $this->store([...$free, 'prefix' => ''])->assertSessionHasErrors('prefix');
        $this->store([...$free, 'applications' => ['web']])->assertSessionHasErrors('applications.0');
        $this->store([...$free, 'selfOrderPaymentMethodCodes' => []])->assertSessionHasErrors('selfOrderPaymentMethodCodes');

        Http::assertNothingSent();
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        Http::fake(['*' => Http::response(OmsPromotion::refused([
            'branchCode' => ['Branch Code LR00 Not Found'],
            'selfOrderPaymentMethodCode' => ['Invalid Self Order Payment Method Code'],
        ]), 400)]);

        $this->store([...self::common(6), ...self::types()['discount (Rp) ESO'][1]])
            ->assertSessionHasErrors([
                'branchCodes' => 'Branch Code LR00 Not Found',
                'selfOrderPaymentMethodCodes' => 'Invalid Self Order Payment Method Code',
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

        $this->store([...self::common(1), ...self::types()['discount (%)'][1]])
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $this->assertSame('promotion.write-unconfirmed', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function one_create_per_person_at_a_time(): void
    {
        Http::fake();

        $manager = $this->manager();
        $lock = Cache::lock('promotion-create:'.$manager->id, 60);
        $lock->get();

        try {
            $this->store([...self::common(1), ...self::types()['discount (%)'][1]], $manager)
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
