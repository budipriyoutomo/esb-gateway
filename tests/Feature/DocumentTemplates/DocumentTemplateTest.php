<?php

namespace Tests\Feature\DocumentTemplates;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDocumentTemplate;
use Tests\TestCase;

/**
 * ESB Core Master Document Template, read-only: GET /document-template and
 * GET /document-template/{requestTemplateID} ([ESB-016]).
 */
class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_URL = 'https://stg7.esb.co.id/core-int/document-template';

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

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function operator(): User
    {
        return $this->userWithRole(Role::IntegrationOperator);
    }

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('document-templates.index'))->assertRedirect(route('login'));
        $this->get(route('document-templates.show', 12))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_document_templates_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('document-templates.index'))->assertForbidden();
            $this->actingAs($user)->get(route('document-templates.show', 12))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function an_operator_can_list_document_templates(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreDocumentTemplate::listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('document-templates.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('document-templates/index')
                ->has('templates', 1)
                ->where('templates.0.id', 12)
                ->where('templates.0.name', 'Weekly Kitchen Order')
                ->where('templates.0.typeNames', ['Purchase Request'])
                ->where('templates.0.branchNames', ['Kemang', 'Senopati'])
                ->where('templates.0.active', true)
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20])
                ->where('types', [
                    ['value' => 1, 'label' => 'Purchase Request'],
                    ['value' => 2, 'label' => 'Usage Management'],
                    ['value' => 3, 'label' => 'Item Journal'],
                ]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'?page=1&limit=20');

        $this->assertSame('document-template.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_list_can_be_filtered_and_paged(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreDocumentTemplate::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('document-templates.index', [
                'page' => 2,
                'requestTemplateName' => 'Kitchen',
                'requestTemplateTypeID' => 2,
                'flagActive' => 0,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', [
                    'page' => 2,
                    'limit' => 20,
                    'requestTemplateName' => 'Kitchen',
                    'requestTemplateTypeID' => 2,
                    'flagActive' => 0,
                ]));

        Http::assertSent(fn (Request $request) => $request->url()
            === self::LIST_URL.'?page=2&limit=20&requestTemplateName=Kitchen&requestTemplateTypeID=2&flagActive=0');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('document-templates.index', [
                'page' => 0,
                'limit' => 101,
                'requestTemplateName' => str_repeat('x', 256),
                'requestTemplateTypeID' => 4,
                'flagActive' => 2,
            ]))
            ->assertSessionHasErrors(['page', 'limit', 'requestTemplateName', 'requestTemplateTypeID', 'flagActive']);

        Http::assertNothingSent();
    }

    // ================================================================= detail

    #[Test]
    public function the_detail_shows_the_template_and_its_lines(): void
    {
        Http::fake([self::LIST_URL.'/12' => Http::response(CoreDocumentTemplate::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('document-templates.show', 12))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('document-templates/show')
                ->where('template.id', 12)
                ->where('template.name', 'Weekly Kitchen Order')
                ->where('template.type', 1)
                ->where('template.typeName', 'Purchase Request')
                ->where('template.branchIds', [3, 4])
                ->has('template.lines', 2)
                ->where('template.lines.0.productCode', 'RM-0001')
                ->where('template.lines.0.purposeId', 7));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'/12');

        $this->assertSame('document-template.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_template_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'path' => self::LIST_URL.'/999',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->operator())
            ->get(route('document-templates.show', 999))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_a_template_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreDocumentTemplate::detailResponse(
            CoreDocumentTemplate::detail(['requestTemplateID' => 0]),
        ))]);

        $this->actingAs($this->operator())
            ->get(route('document-templates.show', 12))
            ->assertNotFound();
    }

    #[Test]
    public function an_id_that_is_not_a_positive_number_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach (['abc', '0', '-1', rawurlencode('1/2'), str_repeat('9', 11)] as $id) {
            $this->actingAs($this->operator())->get('/document-templates/'.$id)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
