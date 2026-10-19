import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import PurchaseOrdersIndex from '@/pages/purchase-orders/index';
import type { PurchaseOrderSummary } from '@/types/purchase-order';

afterEach(() => {
    vi.restoreAllMocks();
});

const order: PurchaseOrderSummary = {
    provider: 'esb',
    number: 'PO-1',
    date: '2026-03-20',
    requiredDate: '2026-03-25',
    branchId: 1,
    branchName: 'Jakarta',
    supplierId: 9,
    supplierName: 'Acme',
    currencyId: 1,
    currencySign: 'Rp',
    total: 1500,
    statusId: 1,
    statusName: 'Authorized',
} as PurchaseOrderSummary;

function renderPage(filters = {}) {
    const get = vi.spyOn(router, 'get').mockImplementation(() => {});
    render(
        <PurchaseOrdersIndex
            purchaseOrders={[order]}
            pagination={{
                page: 1,
                limit: 20,
                count: 1,
                lastPage: 1,
                hasMorePages: false,
            }}
            filters={filters}
        />,
    );

    return get;
}

describe('Purchase orders index', () => {
    it('lists orders with formatted values', () => {
        renderPage();

        expect(screen.getByRole('link', { name: 'PO-1' })).toBeVisible();
        expect(screen.getByText('Rp 1.500,00')).toBeVisible();
        expect(screen.getByText('20 Mar 2026')).toBeVisible();
    });

    it('sends only the filters that have a value', async () => {
        const get = renderPage();

        await userEvent.type(screen.getByRole('searchbox'), 'acme{Enter}');

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/purchase-orders',
            { search: 'acme' },
            { preserveState: true, replace: true },
        );
    });

    it('removing a chip clears that filter, in the query and the field', async () => {
        const get = renderPage({ search: 'acme', dateFrom: '2026-03-01' });

        await userEvent.click(
            screen.getByRole('button', { name: 'Remove filter: “acme”' }),
        );

        expect(get.mock.calls[0][1]).toEqual({ dateFrom: '2026-03-01' });
        expect(screen.getByRole('searchbox')).toHaveValue('');
    });

    it('reset clears every field', async () => {
        const get = renderPage({ search: 'acme', dateFrom: '2026-03-01' });

        await userEvent.click(
            screen.getByRole('button', { name: 'Reset filters' }),
        );

        expect(get.mock.calls[0][1]).toEqual({});
        expect(screen.getByRole('searchbox')).toHaveValue('');
        expect(screen.getByLabelText('Order date from')).toHaveValue('');
    });
});
