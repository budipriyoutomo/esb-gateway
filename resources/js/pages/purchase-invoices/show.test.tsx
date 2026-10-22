import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import PurchaseInvoiceShow from '@/pages/purchase-invoices/show';
import type { Permission } from '@/types';

const granted: { permissions: Permission[] } = { permissions: [] };

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) =>
            granted.permissions.includes(permission),
    }),
}));

beforeEach(() => {
    granted.permissions = [];
});

afterEach(() => {
    vi.restoreAllMocks();
});

const purchaseInvoice = {
    provider: 'esb',
    number: 'PI202603260001',
    supplierInvoiceNumber: null,
    taxInvoiceNumber: null,
    date: '2026-03-26',
    dueDate: null,
    dueDays: null,
    referenceDate: null,
    branchName: 'Outlet Senopati',
    supplierName: 'Acme',
    currencyName: 'Rupiah',
    currencySign: 'Rp',
    rate: 1,
    referenceTotal: null,
    costTotal: null,
    total: 1500,
    statusName: 'New',
    notes: null,
    createdByName: null,
    createdAt: null,
    editedByName: null,
    editedAt: null,
    authorizedByName: null,
    authorizedAt: null,
    lines: [],
    costs: [],
};

function renderPage() {
    render(<PurchaseInvoiceShow purchaseInvoice={purchaseInvoice} />);
}

describe('Purchase invoice detail', () => {
    it('offers no actions without the permissions', () => {
        granted.permissions = ['esb.invoke'];
        renderPage();

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers authorize and reject to an approver, delete to a manager', () => {
        granted.permissions = ['esb.invoke', 'purchase-invoices.approve'];
        renderPage();

        expect(screen.getByRole('button', { name: 'Authorize' })).toBeVisible();
        expect(screen.getByRole('button', { name: 'Reject' })).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Delete' }),
        ).not.toBeInTheDocument();
    });

    it('rejects without asking for a reason (the ESB takes none)', async () => {
        granted.permissions = ['esb.invoke', 'purchase-invoices.approve'];
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        renderPage();

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));

        const dialog = screen.getByRole('dialog');
        expect(
            within(dialog).getByText(
                /The purchase invoice is rejected in the ERP straight away\./,
            ),
        ).toBeVisible();
        expect(within(dialog).queryByRole('textbox')).not.toBeInTheDocument();

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Reject' }),
        );

        expect(post).toHaveBeenCalledExactlyOnceWith(
            '/purchase-invoices/PI202603260001/decisions',
            { decision: 'reject' },
            expect.anything(),
        );
    });

    it('deletes after confirmation', async () => {
        granted.permissions = ['esb.invoke', 'purchase-invoices.manage'];
        const destroy = vi.spyOn(router, 'delete').mockImplementation(() => {});
        renderPage();

        await userEvent.click(screen.getByRole('button', { name: 'Delete' }));
        await userEvent.click(
            within(screen.getByRole('dialog')).getByRole('button', {
                name: 'Delete',
            }),
        );

        expect(destroy).toHaveBeenCalledExactlyOnceWith(
            '/purchase-invoices/PI202603260001',
            expect.anything(),
        );
    });
});
