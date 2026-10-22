import { render, screen } from '@testing-library/react';
import type { ReactElement } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import CoaAccountsIndex from '@/pages/coa-accounts/index';
import DisbursementIndex from '@/pages/disbursements/index';
import GeneralJournalIndex from '@/pages/general-journals/index';
import GeneralLedgerIndex from '@/pages/general-ledger/index';
import GeneralLedgerSummary from '@/pages/general-ledger/summary';
import MemorialJournalIndex from '@/pages/memorial-journals/index';
import PricelistsIndex from '@/pages/pricelists/index';
import ProductCategoriesIndex from '@/pages/product-categories/index';
import ProductSubCategoriesIndex from '@/pages/product-sub-categories/index';
import ProductsIndex from '@/pages/products/index';
import SuppliersIndex from '@/pages/suppliers/index';
import UnitsIndex from '@/pages/units/index';
import type { Permission } from '@/types';

// ADR-031: every exportable list offers its CSV, with the filters it applied
// and without its page. Reading the list already needs esb.invoke, so no
// other permission is asked.
vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) => permission === 'esb.invoke',
        canAny: () => false,
    }),
}));

const paged = { page: 2, limit: 20 };

const cases: [string, ReactElement, string][] = [
    [
        'units',
        <UnitsIndex
            units={[]}
            pagination={null}
            filters={{ ...paged, search: 'KG' }}
        />,
        '/units/export?search=KG',
    ],
    [
        'product categories',
        <ProductCategoriesIndex
            categories={[]}
            pagination={null}
            filters={{ ...paged, flagActive: 1 }}
            categoryTypes={{}}
        />,
        '/product-categories/export?flagActive=1',
    ],
    [
        'product sub-categories',
        <ProductSubCategoriesIndex
            subCategories={[]}
            pagination={null}
            filters={paged}
        />,
        '/product-sub-categories/export',
    ],
    [
        'products',
        <ProductsIndex
            products={[]}
            pagination={null}
            filters={{ ...paged, search: 'Air' }}
        />,
        '/products/export?search=Air',
    ],
    [
        'pricelists',
        <PricelistsIndex entries={[]} pagination={null} filters={paged} />,
        '/pricelists/export',
    ],
    [
        'chart of accounts',
        <CoaAccountsIndex accounts={[]} pagination={null} filters={paged} />,
        '/coa-accounts/export',
    ],
    [
        'suppliers',
        <SuppliersIndex suppliers={[]} pagination={null} filters={paged} />,
        '/suppliers/export',
    ],
    [
        'disbursements',
        <DisbursementIndex
            disbursements={[]}
            pagination={null}
            filters={paged}
            statuses={[]}
        />,
        '/disbursements/export',
    ],
    [
        'general journals',
        <GeneralJournalIndex entries={[]} pagination={null} filters={paged} />,
        '/general-journals/export',
    ],
    [
        'memorial journals',
        <MemorialJournalIndex
            journals={[]}
            pagination={null}
            filters={paged}
            statuses={[]}
        />,
        '/memorial-journals/export',
    ],
    [
        'general ledger',
        <GeneralLedgerIndex
            entries={[]}
            pagination={null}
            filters={{
                ...paged,
                coaNo: '1101',
                dateFrom: '2026-03-01',
                dateTo: '2026-03-31',
            }}
            error={null}
        />,
        '/general-ledger/export?coaNo=1101&dateFrom=2026-03-01&dateTo=2026-03-31',
    ],
    [
        'ledger summary',
        <GeneralLedgerSummary
            rows={[]}
            pagination={null}
            filters={{
                ...paged,
                dateFrom: '2026-03-01',
                dateTo: '2026-03-31',
            }}
            error={null}
        />,
        '/general-ledger/summary/export?dateFrom=2026-03-01&dateTo=2026-03-31',
    ],
];

describe('list exports', () => {
    it.each(cases)(
        '%s offers its CSV with its filters',
        (_name, page, href) => {
            render(page);

            expect(
                screen.getByRole('link', { name: 'Export CSV' }),
            ).toHaveAttribute('href', href);
        },
    );

    it('the ledger offers no export before anything was read', () => {
        render(
            <GeneralLedgerIndex
                entries={null}
                pagination={null}
                filters={{}}
                error={null}
            />,
        );

        expect(
            screen.queryByRole('link', { name: 'Export CSV' }),
        ).not.toBeInTheDocument();
    });
});
