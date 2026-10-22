<?php

namespace App\Http\Controllers\Exports;

use App\Audit\AuditAction;
use App\Domain\Canonical\Pagination;
use App\Exports\CsvExport;
use App\Exports\ListColumns;
use App\Exports\PagedQuery;
use App\Exports\TooManyRowsToExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ListGeneralJournalsRequest;
use App\Http\Requests\Accounting\ListLedgerEntriesRequest;
use App\Http\Requests\Accounting\ListLedgerSummaryRequest;
use App\Http\Requests\Accounting\ListMemorialJournalsRequest;
use App\Http\Requests\CoaAccounts\ListCoaAccountsRequest;
use App\Http\Requests\Disbursements\ListDisbursementsRequest;
use App\Http\Requests\Pricelists\ListPricelistsRequest;
use App\Http\Requests\ProductCategories\ListProductCategoriesRequest;
use App\Http\Requests\Products\ListProductsRequest;
use App\Http\Requests\ProductSubCategories\ListProductSubCategoriesRequest;
use App\Http\Requests\Suppliers\ListSuppliersRequest;
use App\Http\Requests\Units\ListUnitsRequest;
use App\Integrations\Contracts\CoaAccountSource;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\Contracts\GeneralJournalSource;
use App\Integrations\Contracts\GeneralLedgerSource;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\Contracts\ProductCategorySource;
use App\Integrations\Contracts\ProductSource;
use App\Integrations\Contracts\ProductSubCategorySource;
use App\Integrations\Contracts\SupplierSource;
use App\Integrations\Contracts\UnitOfMeasureSource;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * A list as a CSV download, built by the gateway (ADR-031). Each export
 * takes its list's own filters, validated and authorized by the list's
 * FormRequest, and reads every page before the file is written.
 *
 * A list the gateway pages itself from one ESB answer (the general ledger,
 * the chart of accounts) is read once, as one large page.
 */
class ListExportController extends Controller
{
    public function __construct(private readonly CsvExport $csv) {}

    public function units(ListUnitsRequest $request, UnitOfMeasureSource $units): Response|RedirectResponse
    {
        return $this->export('units', 'units.index', $request->validatedFilters(), ListColumns::units(), function (int $page) use ($request, $units): array {
            $result = $units->listUnits(PagedQuery::at($request->toQuery(), $page));

            return [$result->units, $result->pagination];
        });
    }

    public function productCategories(ListProductCategoriesRequest $request, ProductCategorySource $categories): Response|RedirectResponse
    {
        return $this->export('product-categories', 'product-categories.index', $request->validatedFilters(), ListColumns::productCategories(), function (int $page) use ($request, $categories): array {
            $result = $categories->listCategories(PagedQuery::at($request->toQuery(), $page));

            return [$result->categories, $result->pagination];
        });
    }

    public function productSubCategories(ListProductSubCategoriesRequest $request, ProductSubCategorySource $subCategories): Response|RedirectResponse
    {
        return $this->export('product-sub-categories', 'product-sub-categories.index', $request->validatedFilters(), ListColumns::productSubCategories(), function (int $page) use ($request, $subCategories): array {
            $result = $subCategories->listSubCategories(PagedQuery::at($request->toQuery(), $page));

            return [$result->subCategories, $result->pagination];
        });
    }

    public function products(ListProductsRequest $request, ProductSource $products): Response|RedirectResponse
    {
        return $this->export('products', 'products.index', $request->validatedFilters(), ListColumns::products(), function (int $page) use ($request, $products): array {
            $result = $products->listProducts(PagedQuery::at($request->toQuery(), $page));

            return [$result->products, $result->pagination];
        });
    }

    public function pricelists(ListPricelistsRequest $request, PricelistSource $pricelists): Response|RedirectResponse
    {
        return $this->export('pricelists', 'pricelists.index', $request->validatedFilters(), ListColumns::pricelists(), function (int $page) use ($request, $pricelists): array {
            $result = $pricelists->listPrices(PagedQuery::at($request->toQuery(), $page));

            return [$result->entries, $result->pagination];
        });
    }

    public function coaAccounts(ListCoaAccountsRequest $request, CoaAccountSource $accounts): Response|RedirectResponse
    {
        return $this->export('coa-accounts', 'coa-accounts.index', $request->validatedFilters(), ListColumns::coaAccounts(), function (int $page) use ($request, $accounts): array {
            // Paged by the gateway from one answer: read it once.
            $result = $accounts->listAccounts(PagedQuery::at($request->toQuery(), $page, CsvExport::MAX_ROWS));

            return [$result->accounts, $result->pagination];
        });
    }

    public function suppliers(ListSuppliersRequest $request, SupplierSource $suppliers): Response|RedirectResponse
    {
        return $this->export('suppliers', 'suppliers.index', $request->validatedFilters(), ListColumns::suppliers(), function (int $page) use ($request, $suppliers): array {
            $result = $suppliers->listSuppliers(PagedQuery::at($request->toQuery(), $page));

            return [$result->suppliers, $result->pagination];
        });
    }

    public function disbursements(ListDisbursementsRequest $request, DisbursementSource $disbursements): Response|RedirectResponse
    {
        return $this->export('disbursements', 'disbursements.index', $request->validatedFilters(), ListColumns::disbursements(), function (int $page) use ($request, $disbursements): array {
            $result = $disbursements->listDisbursements(PagedQuery::at($request->toQuery(), $page));

            return [$result->disbursements, $result->pagination];
        });
    }

    public function generalJournals(ListGeneralJournalsRequest $request, GeneralJournalSource $journal): Response|RedirectResponse
    {
        return $this->export('general-journals', 'general-journals.index', $request->validatedFilters(), ListColumns::generalJournals(), function (int $page) use ($request, $journal): array {
            $result = $journal->listEntries(PagedQuery::at($request->toQuery(), $page));

            return [$result->entries, $result->pagination];
        });
    }

    public function memorialJournals(ListMemorialJournalsRequest $request, MemorialJournalSource $journals): Response|RedirectResponse
    {
        return $this->export('memorial-journals', 'memorial-journals.index', $request->validatedFilters(), ListColumns::memorialJournals(), function (int $page) use ($request, $journals): array {
            $result = $journals->listJournals(PagedQuery::at($request->toQuery(), $page));

            return [$result->journals, $result->pagination];
        });
    }

    public function ledgerEntries(ListLedgerEntriesRequest $request, GeneralLedgerSource $ledger): Response|RedirectResponse
    {
        $query = $request->toQuery();

        if ($query === null) {
            return $this->refuse('general-ledger.index', 'Choose an account and a period before exporting the ledger.');
        }

        return $this->export('general-ledger', 'general-ledger.index', $request->validatedFilters(), ListColumns::ledgerEntries(), function (int $page) use ($query, $ledger): array {
            // The ERP answers the whole period at once: read it once.
            $result = $ledger->entries(PagedQuery::at($query, $page, CsvExport::MAX_ROWS));

            return [$result->entries, $result->pagination];
        });
    }

    public function ledgerSummary(ListLedgerSummaryRequest $request, GeneralLedgerSource $ledger): Response|RedirectResponse
    {
        $query = $request->toQuery();

        if ($query === null) {
            return $this->refuse('general-ledger.summary', 'Choose a period before exporting the summary.');
        }

        return $this->export('general-ledger-summary', 'general-ledger.summary', $request->validatedFilters(), ListColumns::ledgerSummary(), function (int $page) use ($query, $ledger): array {
            $result = $ledger->summary(PagedQuery::at($query, $page));

            return [$result->rows, $result->pagination];
        });
    }

    /**
     * @template TRow
     *
     * @param  string  $list  File name stem and audit name, e.g. "units".
     * @param  string  $indexRoute  Where a refused export lands, when there is no previous page.
     * @param  array<string, mixed>  $filters  Audited with the export.
     * @param  array<string, Closure(TRow): (string|int|float|bool|null)>  $columns
     * @param  Closure(int): array{list<TRow>, Pagination}  $readPage
     */
    private function export(string $list, string $indexRoute, array $filters, array $columns, Closure $readPage): Response|RedirectResponse
    {
        try {
            $rows = $this->csv->readAll($readPage);
        } catch (TooManyRowsToExport $exception) {
            return $this->refuse($indexRoute, sprintf(
                '%s rows match these filters; an export holds at most %s. Narrow the filters and try again.',
                number_format($exception->count),
                number_format(CsvExport::MAX_ROWS),
            ));
        }

        unset($filters['page'], $filters['limit']);

        AuditLog::record(
            action: AuditAction::ListExported,
            metadata: ['list' => $list, 'filters' => $filters, 'rows' => count($rows)],
        );

        return response($this->csv->toCsv($columns, $rows), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s-%s.csv"', $list, now()->format('Ymd-His')),
            'Cache-Control' => 'no-store',
        ]);
    }

    private function refuse(string $indexRoute, string $message): RedirectResponse
    {
        $this->toast('error', $message);

        return redirect()->to(url()->previous(route($indexRoute)));
    }
}
