<?php

namespace Tests\Feature\DocumentTemplates;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\DocumentTemplateImport;
use App\Models\DocumentTemplateImportEntry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\DocumentTemplateSheet;
use Tests\TestCase;

/**
 * Import document templates from a spreadsheet (ADR-023, [ESB-016]).
 *
 * Three steps, each its own action:
 * - upload: the sheet is read and stored in the gateway's database; the ESB is
 *   not called;
 * - validate: reads only -- branch codes, product code + unit, and whether the
 *   name is already in the ERP -- marking each template ready or blocked;
 * - sync: POSTs each ready template through CreateDocumentTemplate, one at a
 *   time, recording what the ESB said about each.
 */
class DocumentTemplateImportTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://stg7.esb.co.id/core-int';

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

    private function manager(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(mixed $result = null, string $code = 'EC03100000', string $message = 'OK', mixed $errors = null): array
    {
        return [
            'path' => self::BASE,
            'timestamp' => '2026-10-15 10:30:00',
            'status' => $code === 'EC03100000' ? 'ok' : 'fail',
            'code' => $code,
            'message' => $message,
            'result' => $result,
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function page(array $rows): array
    {
        return $this->envelope(['count' => count($rows), 'data' => $rows, 'limit' => 50, 'next' => '', 'page' => 1, 'prev' => '']);
    }

    /**
     * @return array<string, mixed>
     */
    private function queryOf(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function pathOf(Request $request): string
    {
        return (string) parse_url($request->url(), PHP_URL_PATH);
    }

    /**
     * The fake ERP's current state. Http::fake() stacks callbacks and the
     * first one answers, so the fake is registered once and later calls to
     * fakeErp() only change what it answers -- the ERP "changing" between
     * validate and sync.
     *
     * @var array{products: array<string, list<array<string, mixed>>>, existing: list<array<string, mixed>>, onCreate: callable|null}|null
     */
    private ?array $erp = null;

    /**
     * The ERP as the validate and sync steps see it.
     *
     * @param  array<string, list<array<string, mixed>>>  $products  code searched => units of every product
     *                                                               the ESB's partial match returns: productDetailID, productCode,
     *                                                               productName, uomName, and optionally unitActive / productActive
     * @param  list<array<string, mixed>>  $existingTemplates  rows GET /document-template returns
     * @param  callable(Request): mixed|null  $onCreate  what POST /document-template answers
     */
    private function fakeErp(?array $products = null, array $existingTemplates = [], ?callable $onCreate = null): void
    {
        $registered = $this->erp !== null;

        $products ??= [
            'BD0028' => [['productDetailID' => 701, 'productCode' => 'BD0028', 'productName' => 'DIAMOND Fresh Milk', 'uomName' => 'BTL @0.946 LT']],
            'AC0006' => [
                ['productDetailID' => 702, 'productCode' => 'AC0006', 'productName' => 'Bega', 'uomName' => 'PC'],
                ['productDetailID' => 703, 'productCode' => 'AC0006', 'productName' => 'Bega', 'uomName' => 'pack'],
            ],
            // A partial-match neighbour the gateway must not take.
            'EC0080' => [
                ['productDetailID' => 704, 'productCode' => 'EC00801', 'productName' => 'Pakcoy Baby', 'uomName' => 'KG'],
                ['productDetailID' => 705, 'productCode' => 'EC0080', 'productName' => 'Pakcoy', 'uomName' => 'KG'],
            ],
        ];

        $this->erp = ['products' => $products, 'existing' => $existingTemplates, 'onCreate' => $onCreate];

        if ($registered) {
            return;
        }

        Http::fake(function (Request $request) {
            ['products' => $products, 'existing' => $existingTemplates, 'onCreate' => $onCreate] = $this->erp ?? [];
            $path = $this->pathOf($request);
            $query = $this->queryOf($request);

            if ($path === '/core-int/branch') {
                return Http::response($this->envelope([
                    ['branchCode' => 'STHO', 'branchID' => 3, 'branchName' => 'Head Office'],
                    ['branchCode' => 'sttsm', 'branchID' => 4, 'branchName' => 'Outlet TSM'],
                ]));
            }

            // GET /product/list?productCode= answers with every product whose
            // code contains it (partial, like the real ESB); GET
            // /product/{productID} with that product's units.
            if ($path === '/core-int/product/list') {
                $products = $products[$query['productCode'] ?? ''] ?? [];
                $byCode = [];

                foreach ($products as $unit) {
                    $byCode[$unit['productCode']] = [
                        'productID' => $this->productIdOf($unit['productCode']),
                        'productCode' => $unit['productCode'],
                        'productName' => $unit['productName'],
                        'flagActive' => ($unit['productActive'] ?? true) ? 1 : 0,
                    ];
                }

                return Http::response($this->page(array_values($byCode)));
            }

            if (preg_match('#^/core-int/product/(\d+)$#', $path, $match) === 1) {
                $units = array_values(array_filter(
                    array_merge(...array_values($products)),
                    fn (array $unit): bool => $this->productIdOf($unit['productCode']) === (int) $match[1],
                ));

                return Http::response($this->envelope([
                    'productCode' => $units[0]['productCode'] ?? '',
                    'productName' => $units[0]['productName'] ?? '',
                    'flagActive' => $units[0]['productActive'] ?? true,
                    'productDetails' => array_map(static fn (array $unit): array => [
                        'productDetailID' => $unit['productDetailID'],
                        'uomName' => $unit['uomName'],
                        'flagActive' => $unit['unitActive'] ?? true,
                    ], $units),
                ]));
            }

            if ($path === '/core-int/document-template' && $request->method() === 'GET') {
                $name = strtolower((string) ($query['requestTemplateName'] ?? ''));

                return Http::response($this->page(array_values(array_filter(
                    $existingTemplates,
                    static fn (array $row): bool => str_contains(strtolower($row['requestTemplateName']), $name),
                ))));
            }

            if ($path === '/core-int/document-template' && $request->method() === 'POST') {
                return $onCreate !== null
                    ? $onCreate($request)
                    : Http::response($this->envelope(['requestTemplateID' => 900]), 201);
            }

            return Http::response($this->envelope(null, 'EC03100004', 'Not Found'), 404);
        });
    }

    private function productIdOf(string $code): int
    {
        return (int) (crc32($code) % 1_000_000) + 1;
    }

    private function uploadTwoTemplates(User $user): DocumentTemplateImport
    {
        $this->actingAs($user)->post(route('document-template-imports.store'), [
            'file' => DocumentTemplateSheet::upload(DocumentTemplateSheet::twoTemplates()),
        ]);

        return DocumentTemplateImport::query()->latest('id')->firstOrFail();
    }

    private function entry(DocumentTemplateImport $import, string $name): DocumentTemplateImportEntry
    {
        return $import->entries()->where('name', $name)->firstOrFail();
    }

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('document-template-imports.index'))->assertRedirect(route('login'));
        $this->post(route('document-template-imports.store'))->assertRedirect(route('login'));
        $this->get(route('document-template-imports.show', 1))->assertRedirect(route('login'));
        $this->post(route('document-template-imports.validate', 1))->assertRedirect(route('login'));
        $this->post(route('document-template-imports.sync', 1))->assertRedirect(route('login'));
    }

    #[Test]
    public function importing_needs_the_manage_permission(): void
    {
        Http::fake();

        $operator = $this->userWithRole(Role::IntegrationOperator);
        $import = $this->uploadTwoTemplates($this->manager());

        $this->actingAs($operator)->get(route('document-template-imports.index'))->assertForbidden();
        $this->actingAs($operator)->post(route('document-template-imports.store'), [
            'file' => DocumentTemplateSheet::upload(DocumentTemplateSheet::twoTemplates()),
        ])->assertForbidden();
        $this->actingAs($operator)->get(route('document-template-imports.show', $import))->assertForbidden();
        $this->actingAs($operator)->post(route('document-template-imports.validate', $import))->assertForbidden();
        $this->actingAs($operator)->post(route('document-template-imports.sync', $import))->assertForbidden();

        Http::assertNothingSent();
    }

    // ================================================================= upload

    #[Test]
    public function uploading_stores_the_templates_without_calling_the_esb(): void
    {
        Http::fake();

        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('document-template-imports.store'), [
                'file' => DocumentTemplateSheet::upload(DocumentTemplateSheet::twoTemplates()),
            ])
            ->assertRedirect(route('document-template-imports.show', DocumentTemplateImport::query()->sole()))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertNothingSent();

        $import = DocumentTemplateImport::query()->sole();
        $this->assertSame('Document Template ESB_Supplier.xlsx', $import->filename);
        $this->assertSame(64, strlen($import->file_sha256));
        $this->assertSame($manager->id, $import->uploaded_by);
        $this->assertSame(DocumentTemplateImport::STATUS_IDLE, $import->status);

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_PENDING, $sukanda->status);
        $this->assertSame(1, $sukanda->type);
        $this->assertSame(['STTSM', 'STHO'], $sukanda->branch_codes);
        $this->assertNull($sukanda->branch_ids);
        $this->assertSame([], $sukanda->problems);
        $this->assertSame(1, $sukanda->position);

        $lines = $sukanda->lines()->orderBy('row_number')->get();
        $this->assertSame(['BD0028', 'AC0006'], $lines->pluck('product_code')->all());
        $this->assertSame(['BEV', 'FOD'], $lines->pluck('category')->all());
        $this->assertSame([3, 4], $lines->pluck('row_number')->all());
        $this->assertSame([null, null], $lines->pluck('product_detail_id')->all());

        $this->assertSame(2, $this->entry($import, 'Template PR Olih')->position);

        $audit = AuditLog::query()->sole();
        $this->assertSame('document-template-import.uploaded', $audit->action);
        $this->assertSame($import->id, $audit->metadata['import_id']);
        $this->assertSame(2, $audit->metadata['templates']);
        $this->assertSame(3, $audit->metadata['lines']);
    }

    #[Test]
    public function templates_with_problems_in_the_sheet_are_stored_as_invalid(): void
    {
        Http::fake();

        $this->actingAs($this->manager())->post(route('document-template-imports.store'), [
            'file' => DocumentTemplateSheet::upload([
                [1, 'Template A', 'Purchase Request', 'STHO', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
                [2, 'Template B', 'Purchase Order', 'STHO', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
                [3, null, 'Purchase Request', 'STHO', 'VEG', 'Bayam', 'EC0081', 'KG'],
            ]),
        ]);

        $import = DocumentTemplateImport::query()->sole();

        $this->assertSame(DocumentTemplateImportEntry::STATUS_PENDING, $this->entry($import, 'Template A')->status);

        $b = $this->entry($import, 'Template B');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_INVALID, $b->status);
        $this->assertSame(['Row 4: unknown template type "Purchase Order" (expected Purchase Request, Usage Management, or Item Journal).'], $b->problems);
        $this->assertNull($b->type);

        $this->assertSame(['Row 5: product EC0081 has no template name.'], $import->problems);
    }

    #[Test]
    public function a_file_that_is_not_a_template_sheet_is_refused_and_nothing_is_stored(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('document-template-imports.store'), ['file' => UploadedFile::fake()->create('templates.csv', 1, 'text/csv')])
            ->assertSessionHasErrors('file');

        $this->actingAs($manager)
            ->post(route('document-template-imports.store'), ['file' => UploadedFile::fake()->create('big.xlsx', 3000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])
            ->assertSessionHasErrors('file');

        $wrongLayout = DocumentTemplateSheet::upload([]);
        $this->actingAs($manager)
            ->post(route('document-template-imports.store'), ['file' => $wrongLayout])
            ->assertSessionHasErrors(['file' => 'The sheet has no template rows.']);

        $this->assertSame(0, DocumentTemplateImport::query()->count());
    }

    #[Test]
    public function the_import_page_lists_templates_with_their_lines_and_status(): void
    {
        Http::fake();

        $import = $this->uploadTwoTemplates($this->manager());

        $this->get(route('document-template-imports.show', $import))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('document-template-imports/show')
                ->where('import.id', $import->id)
                ->where('import.filename', 'Document Template ESB_Supplier.xlsx')
                ->where('import.status', 'idle')
                ->where('busy', false)
                ->has('entries', 2)
                ->where('entries.0.name', 'Template SUKANDA')
                ->where('entries.0.status', 'pending')
                ->where('entries.0.typeName', 'Purchase Request')
                ->where('entries.0.branchCodes', ['STTSM', 'STHO'])
                ->has('entries.0.lines', 2)
                ->where('entries.0.lines.0.productCode', 'BD0028')
                ->where('entries.0.lines.0.category', 'BEV')
                ->where('counts.pending', 2));

        $this->get(route('document-template-imports.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('document-template-imports/index')
                ->where('imports.0.id', $import->id)
                ->where('imports.0.templates', 2));
    }

    // =============================================================== validate

    #[Test]
    public function validating_resolves_branches_and_products_and_marks_templates_ready(): void
    {
        $this->fakeErp();

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        $this->actingAs($manager)
            ->post(route('document-template-imports.validate', $import))
            ->assertRedirect(route('document-template-imports.show', $import));

        $import->refresh();
        $this->assertSame(DocumentTemplateImport::STATUS_IDLE, $import->status);
        $this->assertNotNull($import->validated_at);
        $this->assertNull($import->last_error);

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_READY, $sukanda->status, implode(' ', $sukanda->problems));
        $this->assertSame([4, 3], $sukanda->branch_ids);
        // Units match ignoring case and spacing.
        $this->assertSame([701, 703], $sukanda->lines()->orderBy('row_number')->pluck('product_detail_id')->all());

        $olih = $this->entry($import, 'Template PR Olih');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_READY, $olih->status);
        // The exact code, not the partial-match neighbour.
        $this->assertSame([705], $olih->lines()->pluck('product_detail_id')->all());

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
        Http::assertSent(fn (Request $request): bool => $this->pathOf($request) === '/core-int/product/list'
            && $this->queryOf($request) === ['page' => '1', 'limit' => '50', 'productCode' => 'EC0080']);
        // Units are read only for the exact code, not for the neighbour.
        Http::assertSent(fn (Request $request): bool => $this->pathOf($request) === '/core-int/product/'.$this->productIdOf('EC0080'));
        Http::assertNotSent(fn (Request $request): bool => $this->pathOf($request) === '/core-int/product/'.$this->productIdOf('EC00801'));
        Http::assertSent(fn (Request $request): bool => $this->pathOf($request) === '/core-int/document-template'
            && ($this->queryOf($request)['requestTemplateName'] ?? null) === 'Template PR Olih');

        $this->assertSame('document-template-import.validation-requested', AuditLog::query()->latest('id')->first()?->action);
    }

    #[Test]
    public function what_the_erp_does_not_know_blocks_the_template_with_the_reason(): void
    {
        $this->fakeErp(
            products: [
                'BD0028' => [],
                'AC0006' => [['productDetailID' => 702, 'productCode' => 'AC0006', 'productName' => 'Bega', 'uomName' => 'PC']],
                'EC0080' => [['productDetailID' => 705, 'productCode' => 'EC0080', 'productName' => 'Pakcoy', 'uomName' => 'KG']],
            ],
            existingTemplates: [['requestTemplateID' => 55, 'requestTemplateName' => 'template pr olih', 'requestTemplateTypeNames' => 'Purchase Request', 'branchNames' => 'HO', 'flagActive' => 1]],
        );

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_BLOCKED, $sukanda->status);
        $this->assertSame([
            'Row 3: product code BD0028 is not in the ERP.',
            'Row 4: product AC0006 has no unit "PACK" in the ERP (it has: PC).',
        ], $sukanda->problems);

        $olih = $this->entry($import, 'Template PR Olih');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_BLOCKED, $olih->status);
        $this->assertSame(['A template named "template pr olih" already exists in the ERP (id 55). Rename the template in the file, or check it in the ERP.'], $olih->problems);
    }

    #[Test]
    public function an_unknown_branch_code_blocks_the_template(): void
    {
        $this->fakeErp();

        $manager = $this->manager();
        $this->actingAs($manager)->post(route('document-template-imports.store'), [
            'file' => DocumentTemplateSheet::upload([
                [1, 'Template X', 'Purchase Request', 'STHO, STJAWA', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            ]),
        ]);
        $import = DocumentTemplateImport::query()->sole();

        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $entry = $this->entry($import, 'Template X');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_BLOCKED, $entry->status);
        $this->assertSame(['Branch code STJAWA is not an active branch in the ERP.'], $entry->problems);
    }

    #[Test]
    public function invalid_templates_are_not_looked_up(): void
    {
        $this->fakeErp();

        $manager = $this->manager();
        $this->actingAs($manager)->post(route('document-template-imports.store'), [
            'file' => DocumentTemplateSheet::upload([
                [1, 'Template B', 'Purchase Order', 'STHO', 'VEG', 'Pakcoy', 'EC0080', 'KG'],
            ]),
        ]);
        $import = DocumentTemplateImport::query()->sole();

        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $this->assertSame(DocumentTemplateImportEntry::STATUS_INVALID, $this->entry($import, 'Template B')->status);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/product/'));
    }

    #[Test]
    public function inactive_products_and_units_are_not_used(): void
    {
        $this->fakeErp(products: [
            'BD0028' => [['productDetailID' => 701, 'productCode' => 'BD0028', 'productName' => 'Milk', 'uomName' => 'BTL @0.946 LT', 'productActive' => false]],
            'AC0006' => [
                ['productDetailID' => 702, 'productCode' => 'AC0006', 'productName' => 'Bega', 'uomName' => 'PC'],
                ['productDetailID' => 703, 'productCode' => 'AC0006', 'productName' => 'Bega', 'uomName' => 'PACK', 'unitActive' => false],
            ],
            'EC0080' => [['productDetailID' => 705, 'productCode' => 'EC0080', 'productName' => 'Pakcoy', 'uomName' => 'KG']],
        ]);

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $this->assertSame([
            'Row 3: product BD0028 is inactive in the ERP.',
            'Row 4: product AC0006 has no unit "PACK" in the ERP (it has: PC).',
        ], $this->entry($import, 'Template SUKANDA')->problems);
    }

    #[Test]
    public function an_esb_failure_while_validating_leaves_the_templates_as_they_were(): void
    {
        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect'));

        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $import->refresh();
        $this->assertSame(DocumentTemplateImport::STATUS_IDLE, $import->status);
        $this->assertNotNull($import->last_error);
        $this->assertStringStartsWith('Validation stopped: ', $import->last_error);
        // safeMessage() already carries the reference; it is not added twice.
        $this->assertSame(1, substr_count($import->last_error, '(reference: '));
        $this->assertSame(
            [DocumentTemplateImportEntry::STATUS_PENDING],
            $import->entries()->distinct()->pluck('status')->all(),
        );
    }

    // =================================================================== sync

    #[Test]
    public function syncing_creates_each_ready_template_in_the_erp(): void
    {
        $created = 0;
        $this->fakeErp(onCreate: function () use (&$created) {
            $created++;

            return Http::response($this->envelope(['requestTemplateID' => 900 + $created]), 201);
        });

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $this->actingAs($manager)
            ->post(route('document-template-imports.sync', $import))
            ->assertRedirect(route('document-template-imports.show', $import));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->data() === [
                'requestTemplateName' => 'Template SUKANDA',
                'requestTemplateTypeID' => 1,
                'flagActive' => 1,
                'branchIDs' => [4, 3],
                'requestTemplateDetails' => [
                    ['productDetailID' => 701, 'requestTemplateDetailName' => 'BEV'],
                    ['productDetailID' => 703, 'requestTemplateDetailName' => 'FOD'],
                ],
            ]);

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_SYNCED, $sukanda->status);
        $this->assertSame(901, $sukanda->esb_template_id);
        $this->assertNotNull($sukanda->synced_at);

        $olih = $this->entry($import, 'Template PR Olih');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_SYNCED, $olih->status);
        $this->assertSame(902, $olih->esb_template_id);

        $this->assertSame(DocumentTemplateImport::STATUS_IDLE, $import->refresh()->status);
        $this->assertNotNull($import->synced_at);

        $creates = AuditLog::query()->where('action', 'document-template.created')->get();
        $this->assertCount(2, $creates);
        $this->assertSame([$manager->id], $creates->pluck('actor_id')->unique()->values()->all());
        $this->assertSame($import->id, $creates->first()?->metadata['import_id']);
    }

    #[Test]
    public function only_ready_templates_are_sent(): void
    {
        $this->fakeErp(products: [
            'BD0028' => [],
            'AC0006' => [],
            'EC0080' => [['productDetailID' => 705, 'productCode' => 'EC0080', 'productName' => 'Pakcoy', 'uomName' => 'KG']],
        ]);

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));
        $this->actingAs($manager)->post(route('document-template-imports.sync', $import));

        Http::assertSentCount(
            1 + 3 + 1 + 2 // validate: branches, three product searches, one product's units, two name checks
            + 1 + 1, // sync: one name re-check, one create
        );
        $this->assertSame(DocumentTemplateImportEntry::STATUS_BLOCKED, $this->entry($import, 'Template SUKANDA')->status);
        $this->assertSame(DocumentTemplateImportEntry::STATUS_SYNCED, $this->entry($import, 'Template PR Olih')->status);
    }

    #[Test]
    public function a_name_that_appeared_in_the_erp_after_validation_is_not_created_again(): void
    {
        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        $this->fakeErp();
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        // Someone creates "Template PR Olih" in the ERP before the sync runs.
        $this->fakeErp(existingTemplates: [['requestTemplateID' => 77, 'requestTemplateName' => 'Template PR Olih', 'requestTemplateTypeNames' => '', 'branchNames' => '', 'flagActive' => 1]]);
        $this->actingAs($manager)->post(route('document-template-imports.sync', $import));

        $olih = $this->entry($import, 'Template PR Olih');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_BLOCKED, $olih->status);
        $this->assertStringContainsString('already exists in the ERP (id 77)', $olih->problems[0]);
        $this->assertSame(DocumentTemplateImportEntry::STATUS_SYNCED, $this->entry($import, 'Template SUKANDA')->status);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->data()['requestTemplateName'] === 'Template SUKANDA');
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->data()['requestTemplateName'] === 'Template PR Olih');
    }

    #[Test]
    public function a_refused_template_is_failed_with_the_esbs_reason_and_the_next_one_is_still_sent(): void
    {
        $this->fakeErp(onCreate: fn (Request $request) => $request->data()['requestTemplateName'] === 'Template SUKANDA'
            ? Http::response($this->envelope(null, 'EC03100003', 'Validation Error', [
                ['attribute' => 'branchIDs', 'field' => 'branchIDs', 'code' => 'EC03100003', 'message' => 'branch 3 is inactive'],
            ]), 400)
            : Http::response($this->envelope(['requestTemplateID' => 950]), 201));

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));
        $this->actingAs($manager)->post(route('document-template-imports.sync', $import));

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_FAILED, $sukanda->status);
        $this->assertSame(['The ERP refused it: branch 3 is inactive'], $sukanda->problems);
        $this->assertNotNull($sukanda->correlation_id);

        $this->assertSame(DocumentTemplateImportEntry::STATUS_SYNCED, $this->entry($import, 'Template PR Olih')->status);
    }

    #[Test]
    public function a_timed_out_create_is_unconfirmed_not_retried_and_stops_the_run(): void
    {
        $posts = 0;
        $this->fakeErp(onCreate: function () use (&$posts): never {
            $posts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));
        $this->actingAs($manager)->post(route('document-template-imports.sync', $import));

        $this->assertSame(1, $posts);

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_UNCONFIRMED, $sukanda->status);
        $this->assertStringContainsString('did not confirm', $sukanda->problems[0]);
        $this->assertNotNull($sukanda->correlation_id);

        // The ESB may be down: the rest wait for the next run.
        $this->assertSame(DocumentTemplateImportEntry::STATUS_READY, $this->entry($import, 'Template PR Olih')->status);
        $this->assertNotNull($import->refresh()->last_error);

        $this->assertSame('document-template.write-unconfirmed', AuditLog::query()->where('action', 'like', 'document-template.%')->latest('id')->first()?->action);
    }

    #[Test]
    public function validating_again_settles_an_unconfirmed_template(): void
    {
        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        $this->fakeErp(onCreate: fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));
        $this->actingAs($manager)->post(route('document-template-imports.sync', $import));
        $this->assertSame(DocumentTemplateImportEntry::STATUS_UNCONFIRMED, $this->entry($import, 'Template SUKANDA')->status);

        // It did reach the ERP after all.
        $this->fakeErp(existingTemplates: [['requestTemplateID' => 88, 'requestTemplateName' => 'Template SUKANDA', 'requestTemplateTypeNames' => '', 'branchNames' => '', 'flagActive' => 1]]);
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $sukanda = $this->entry($import, 'Template SUKANDA');
        $this->assertSame(DocumentTemplateImportEntry::STATUS_SYNCED, $sukanda->status);
        $this->assertSame(88, $sukanda->esb_template_id);
        $this->assertSame([], $sukanda->problems);
    }

    #[Test]
    public function an_unconfirmed_template_the_erp_does_not_have_is_ready_again(): void
    {
        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        $this->fakeErp(onCreate: fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));
        $this->actingAs($manager)->post(route('document-template-imports.sync', $import));

        $this->fakeErp();
        $this->actingAs($manager)->post(route('document-template-imports.validate', $import));

        $this->assertSame(DocumentTemplateImportEntry::STATUS_READY, $this->entry($import, 'Template SUKANDA')->status);
    }

    #[Test]
    public function syncing_with_nothing_ready_does_nothing(): void
    {
        Http::fake();

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);

        $this->actingAs($manager)
            ->post(route('document-template-imports.sync', $import))
            ->assertRedirect(route('document-template-imports.show', $import))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_busy_import_cannot_be_validated_or_synced_again(): void
    {
        Http::fake();

        $manager = $this->manager();
        $import = $this->uploadTwoTemplates($manager);
        $import->update(['status' => DocumentTemplateImport::STATUS_SYNCING]);

        $this->actingAs($manager)
            ->post(route('document-template-imports.validate', $import))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        $this->actingAs($manager)
            ->post(route('document-template-imports.sync', $import))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        Http::assertNothingSent();
        $this->assertSame(DocumentTemplateImport::STATUS_SYNCING, $import->refresh()->status);
    }
}
