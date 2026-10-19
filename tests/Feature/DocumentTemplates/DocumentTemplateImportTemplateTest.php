<?php

namespace Tests\Feature\DocumentTemplates;

use App\Authorization\Role;
use App\Imports\DocumentTemplates\DocumentTemplateSheetParser;
use App\Imports\DocumentTemplates\DocumentTemplateSheetReader;
use App\Imports\DocumentTemplates\InvalidDocumentTemplateSheet;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * The blank spreadsheet to fill in for an import (ADR-023).
 *
 * Its first sheet is what the import reads: the header only, so nothing in it
 * is ever imported by mistake. Instructions with an example, and the ERP's
 * branch codes as of the download, sit on later sheets the import ignores.
 */
class DocumentTemplateImportTemplateTest extends TestCase
{
    use RefreshDatabase;

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

    private function fakeBranches(): void
    {
        Http::fake(['*/core-int/branch*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/branch',
            'timestamp' => '2026-10-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                ['branchCode' => 'STTSM', 'branchID' => 4, 'branchName' => 'Outlet TSM'],
                ['branchCode' => 'HOF', 'branchID' => 3, 'branchName' => 'Head Office'],
            ],
            'errors' => null,
        ])]);
    }

    /**
     * Every sheet of a downloaded workbook: name => rows of cell values.
     *
     * @return array<string, list<list<mixed>>>
     */
    private function sheetsOf(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $sheets = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = $row->toArray();
            }
        }

        $reader->close();

        return $sheets;
    }

    private function download(): string
    {
        $response = $this->actingAs($this->manager())
            ->get(route('document-template-imports.template'))
            ->assertOk()
            ->assertDownload('Template Import Document Template.xlsx');

        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        return $response->baseResponse->getFile()->getPathname();
    }

    #[Test]
    public function downloading_the_template_needs_the_manage_permission(): void
    {
        Http::fake();

        $this->get(route('document-template-imports.template'))->assertRedirect(route('login'));

        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $this->actingAs($operator)->get(route('document-template-imports.template'))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_first_sheet_is_the_header_the_import_expects_and_nothing_else(): void
    {
        $this->fakeBranches();

        $sheets = $this->sheetsOf($this->download());
        $first = array_key_first($sheets);

        $this->assertSame('Template', $first);
        $this->assertSame([[
            'No',
            'Document Template Name',
            'Document Template Type',
            'Branch Aktif',
            'Kategori',
            'Product Name',
            'Product Code',
            'Unit',
        ]], $sheets[$first]);
    }

    #[Test]
    public function the_import_accepts_the_blank_template_layout(): void
    {
        $this->fakeBranches();

        $rows = (new DocumentTemplateSheetReader)->read($this->download());

        // The header is recognised; the only complaint is that it is empty.
        $this->expectException(InvalidDocumentTemplateSheet::class);
        $this->expectExceptionMessage('The sheet has no template rows.');

        (new DocumentTemplateSheetParser)->parse($rows);
    }

    #[Test]
    public function the_instructions_sheet_explains_the_columns_with_an_example(): void
    {
        $this->fakeBranches();

        $instructions = $this->sheetsOf($this->download())['Petunjuk'];
        $text = implode("\n", array_map(static fn (array $row): string => implode(' | ', array_map('strval', $row)), $instructions));

        foreach (['Document Template Name', 'Document Template Type', 'Branch Aktif', 'Kategori', 'Product Code', 'Unit'] as $column) {
            $this->assertStringContainsString($column, $text);
        }

        $this->assertStringContainsString('Purchase Request', $text);
        $this->assertStringContainsString('Usage Management', $text);
        $this->assertStringContainsString('Item Journal', $text);
        $this->assertStringContainsString('Contoh', $text);
    }

    #[Test]
    public function the_branch_sheet_lists_the_erps_branch_codes_as_of_the_download(): void
    {
        $this->fakeBranches();

        $branches = $this->sheetsOf($this->download())['Cabang ERP'];

        $this->assertSame(['Kode cabang', 'Nama cabang'], $branches[0]);
        $this->assertContains(['HOF', 'Head Office'], $branches);
        $this->assertContains(['STTSM', 'Outlet TSM'], $branches);
    }

    #[Test]
    public function the_template_still_downloads_when_the_esb_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect'));

        $sheets = $this->sheetsOf($this->download());

        $this->assertSame('Template', array_key_first($sheets));
        $this->assertStringContainsString('could not be read from the ERP', implode(' ', array_map('strval', $sheets['Cabang ERP'][0])));
    }
}
